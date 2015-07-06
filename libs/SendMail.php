<?
/**
 * Class SendMail
 * отправка емейлов через smtp сервера в соовтествии с rfc в кодировке utf-8
 * TODO: запилить вложения файлов
 */
class SendMail {

	/* @var null|resource */
	private $connect = null;

	/* @var int */
	private $connect_errno;

	/* @var string */
	private $connect_errstr;

	/* @var bool */
	private $debug = false;

	/* @var int */
	private $err_num;

	/* @var array */
	private $smtp_err_codes = [
		421 => 'Service not available, closing channel',
		432 => 'A password transition is needed',
		450 => 'Requested mail action not taken: mailbox unavailable',
		451 => 'Requested action aborted: error in processing',
		452 => 'Requested action not taken: insufficient system storage',
		454 => 'Temporary authentication failure',
		500 => 'Syntax error, command not recognized',
		501 => 'Syntax error in parameters or arguments',
		502 => 'Command not implemented',
		503 => 'Bad sequence of commands',
		504 => 'Command parameter not implemented',
		530 => 'Authentication required',
		534 => 'Authentication mechanism is too weak',
		535 => 'Authentication failed',
		538 => 'Encryption required for requested authentication mechanism',
		550 => 'Requested action not taken: mailbox unavailable',
		551 => 'User not local, please try forwarding',
		552 => 'Requested mail action aborted: exceeding storage allocation',
		553 => 'Requested action not taken: mailbox name not allowed',
		554 => 'Transaction failed'
	];

	/* @var string */
	private $server_response;

	/* @var array|string */
	private $sender	= '';

	/* @var string */
	private $server	= 'localhost';

	/* @var bool */
	private $login	= false;

	/* @var bool */
	private $pass	= false;

	/* @var string */
	private $auth_method = 'cram-md5';

	/* @var string */
	private $mime_type = 'text/plain';

	/**
	 * @param array $default_params
	 */
	public function __construct($default_params) {
		$this->sender = ['admin@'.$_SERVER['HTTP_HOST'] => $_SERVER['HTTP_HOST']];
		if ($default_params) $this->apply_params($default_params);
	}

	/**
	 * @param array $params
	 */
	private function apply_params($params) {
		$allow_options = ['sender', 'server', 'login', 'pass', 'mime_type', 'auth_method', 'debug'];
		foreach ($allow_options as $key) if (isset($params[$key])) $this->$key = $params[$key];
	}

	/** отправка мыла напрямую через tcp подключение на порт smtp сервера
	 * @param string     $mess       сообщение
	 * @param array      $recipients получатели
	 * @param string     $subject    тема
	 * @param array|null $params	 доп параметры экземляра класса
	 * @return bool			 true если все в порядке и письмо успешно отправленно либо false если гдето ошибка, в случае ошибки информацию можно получить через метод get_last_error()
	 */
	function send($mess, $recipients, $subject='Письмо', $params=null) {
		if (!function_exists('mimeheader')) {
			function mimeheader($str) {
				return '=?utf-8?B?'.base64_encode($str).'?=';
			}
		}
		if (!function_exists('prep')) {
			function prep($data) {
				$return = [];
				foreach ($data as $mail => $name) $return[] = mimeheader($name).' <'.$mail.'>';
				return implode(', ', $return);
			}
		}

		if ($params) $this->apply_params($params);

		$domain  = substr(key($this->sender), strpos(key($this->sender), '@')+1);
		$sender = prep($this->sender);
		$mess = [
			'Sender: '.$sender,
			'From: '.$sender,
			'To: '.prep($recipients),
			'Subject: '.mimeheader($subject),
			'MIME-Version: 1.0',
			'User-Agent: WebDev php mailer (dev@webdev-studio.ru; http://webdev-studo.ru)',
			'Date: '.date('r'),
			'X-Priority: 3',
			'X-MSMail-Priority: Normal',
			'X-Mailer: WebDev php mailer',
			'X-Powered-By: nikll <dev@webdev-studio.ru>',
			'X-Descriptions: '.$_SERVER['HTTP_HOST'].' Powered by nikll <dev@webdev-studio.ru>',
			'Content-Type: '.$this->mime_type.'; charset="utf-8"',
			'Content-Transfer-Encoding: base64',
			'',
			chunk_split(base64_encode($mess)).'.'
		];

		$return = [];

		if (!$this->connect = fsockopen($this->server, 25, $this->connect_errno, $this->connect_errstr)) {
			$this->log("Can't open SMTP connect. \n".$this->connect_errstr." (".$this->connect_errno.")");
			return false;
		}

		if (!$this->step()) return false;
		if (!$this->step('EHLO '.$domain) && $this->err_num >= 500  && !$this->step('HELO '.$domain)) return false;

		$steps = [];
		if ($this->login && $this->pass) {
			switch ($this->auth_method) {
				case 'cram-md5': if ($this->step('AUTH CRAM-MD5') && $this->step(base64_encode($this->login.' '.hash_hmac('md5', base64_decode($this->server_response), $this->pass)))) break;
				case 'plain':	 if ($this->step('AUTH PLAIN '.base64_encode($this->login.chr(0).$this->login.chr(0).$this->pass))) break;
				case 'login':
				default:	 $steps = ['AUTH LOGIN', base64_encode($this->login), base64_encode($this->pass)];
			}
		}
		$steps[] = 'MAIL FROM: <'.key($this->sender).'>';

		foreach ($steps as $send) if (!$this->step($send)) return false;

		foreach ($recipients as $mail => $name) {
			if ($this->step('RCPT TO: <'.$mail.'>'))
				$return[$mail] = true;
			elseif ($this->err_num == 550 || $this->err_num == 552)
				$return[$mail] = false;
			else	return false;
		}
		$steps = [
			'DATA',
			implode("\n", $mess),
			'RSET'."\n".'QUIT'
		];
		foreach ($steps as $send) if (!$this->step($send)) return false;

		fclose($this->connect);

		return true;
	}

	/** возвращает информацию о послдней ошибке
	 * @return array код и расшифровка ошибки
	 */
	public function get_last_error() {
		return [
			'err_num' => $this->err_num,
			'message' => (isset($this->smtp_err_codes[$this->err_num]) ? $this->smtp_err_codes[$this->err_num] : 'Unknown response, error code: '.$this->err_num).';  Server: '.$this->server_response
		];
	}

	/** метод для отправки сообщения серверу и проверки ответа
	 * @param null|string $send Сообщение серверу (если не указывать то просто прочитает ответ сервера)
	 * @return bool
	 */
	private function step($send=null) {
		if ($this->debug) $this->log('send: '.$send);

		if ($send !== null) fwrite($this->connect, $this->crlf($send));
		$line = fread($this->connect, 1024);
		$this->err_num = intval(substr($line, 0, 3));
		$this->server_response = substr($line, 4);

		if ($this->debug) $this->log($line);

		if ($this->err_num < 400) return true;
		return false;
	}

	/** пишет в лог
	 * @param string $text строка для записи в лог
	 * @return int
	 */
	private function log($text) {
		return file_put_contents(ROOT_PATH.'logs/mail_debug.log', $text."\n", FILE_APPEND);
	}

	/** преобразует окончания строк к виду CRLF и заканчивает строку если она не закончена
	 * @param string $s
	 * @return mixed
	 */
	public function crlf($s) {
		return str_replace("\n", "\r\n", $this->lf($s));
	}

	/** преобразует окончания строк к виду LF и заканчивает строку если она не закончена
	 * @param string $s
	 * @return mixed
	 */
	public function lf($s) {
		if ($s{strlen($s)-1} != "\n") $s .= "\n";
		return str_replace(["\r\n", "\r", "\n\r"], "\n", $s);
	}

}
?>