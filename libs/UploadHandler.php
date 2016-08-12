<?

class UploadHandler {
    protected $dir_path = '/gallery/';

    protected $types2ext = [
        IMAGETYPE_GIF     => 'gif',
        IMAGETYPE_JPEG    => 'jpg',
        IMAGETYPE_PNG     => 'png',
        IMAGETYPE_SWF     => 'swf',
        IMAGETYPE_PSD     => 'psd',
        IMAGETYPE_BMP     => 'bmp',
        IMAGETYPE_TIFF_II => 'tiff',
        IMAGETYPE_TIFF_MM => 'tiff',
        IMAGETYPE_JPC     => 'jpc',
        IMAGETYPE_JP2     => 'jp2',
        IMAGETYPE_JPX     => 'jpx',
        IMAGETYPE_JB2     => 'jb2',
        IMAGETYPE_SWC     => 'swc',
        IMAGETYPE_IFF     => 'iff',
        IMAGETYPE_WBMP    => 'wbmp',
        IMAGETYPE_XBM     => 'xbm',
        IMAGETYPE_ICO     => 'ico',
    ];

    protected $error_messages = [
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload',

        'post_max_size'       => 'The uploaded file exceeds the post_max_size directive in php.ini',
        'max_file_size'       => 'Файл слижком большой',
        'accept_file_types'   => 'Недопустимый тип файла',
        'max_number_of_files' => 'Превышено количество файлов',
        'abort'               => 'Загрузка файла прервана'
    ];

    protected $accept_file_types = '/.+$/i';

    protected $max_file_size = 0;
    protected $max_number_of_files = 0;

    public function upload() {
        $file_name = (isset($_SERVER['HTTP_CONTENT_DISPOSITION']) ? rawurldecode(preg_replace('/(^[^"]+")|("$)/', '', $_SERVER['HTTP_CONTENT_DISPOSITION'])) : null);
        $content_range = (isset($_SERVER['HTTP_CONTENT_RANGE']) ? preg_split('/[^0-9]+/', $_SERVER['HTTP_CONTENT_RANGE']) : null);
        $size = ($content_range ? $content_range[3] : null);
        $files = [];

        if (!empty($_FILES['files'])) {
            $upload = $_FILES['files'];
            if (is_array($upload['tmp_name'])) {
                foreach ($upload['tmp_name'] as $index => $value) {
                    $files[] = $this->handle_file_upload(
                        $upload['tmp_name'][$index],
                        ($file_name ? $file_name : $upload['name'][$index]),
                        fix_integer_overflow(intval($size ? $size : $upload['size'][$index])),
                        $upload['type'][$index],
                        $upload['error'][$index],
                        $index,
                        $content_range
                    );
                }
            } else {
                $files[] = $this->handle_file_upload(
                    @$upload['tmp_name'],
                    ($file_name ? $file_name : @$upload['name']),
                    fix_integer_overflow(intval($size ? $size : (isset($upload['size']) ? $upload['size'] : @$_SERVER['CONTENT_LENGTH']))),
                    (isset($upload['type']) ? $upload['type'] : @$_SERVER['CONTENT_TYPE']),
                    @$upload['error'],
                    null,
                    $content_range
                );
            }
        }

        $files = [[
            'name'         => 'picture1.jpg',
            'size'         => 902604,
            'url'          => 'http://example.org/files/picture1.jpg',
            'thumbnailUrl' => 'http://example.org/files/thumbnail/picture1.jpg',
            'deleteUrl'    => 'http://example.org/files/picture1.jpg', 'deleteType' => 'DELETE'
        ]];

        return $files;

    }

    public function handle_file_upload($upload_file, $name, $size, $type, $error, $index = null, $content_range = null) {
        $url_dir = '/'.$this->dir_path.'/';
        $tmp_dir = WWW_PATH.$this->dir_path.'/';
        if (!file_exists($tmp_dir)) mkdir($tmp_dir, 0700, true);

        if (!$image_type = exif_imagetype($upload_file)) return false;
        $image_type = '.'.$this->types2ext[$image_type];

        do {
            $fileName = md5($tmp_dir.microtime(true).$image_type).$image_type;
        } while (file_exists($tmp_dir.$fileName));

        if (!move_uploaded_file($upload_file, $tmp_dir.$fileName)) return false;

        $file_size = fix_integer_overflow(intval($size));

        return [];
    }

    protected function get_error_message($error) {
        return (isset($this->error_messages[$error]) ? $this->error_messages[$error] : $error);
    }

    protected function validate($uploaded_file, $file_name, $error, $index) {
        if ($error) return $this->get_error_message($error);

        $content_length = fix_integer_overflow(intval(@$_SERVER['CONTENT_LENGTH']));
        $post_max_size  = from_human_bytes(ini_get('post_max_size'));

        if ($post_max_size && ($content_length > $post_max_size)) return $this->get_error_message('post_max_size');

        if (!preg_match($this->accept_file_types,$file_name)) return $this->get_error_message('accept_file_types');

        if ($uploaded_file && is_uploaded_file($uploaded_file))
            $file_size = get_file_size($uploaded_file);
        else    $file_size = $content_length;

        if ($this->max_file_size && $file_size > $this->max_file_size) return $this->get_error_message('max_file_size');

        if ($this->max_number_of_files && $this->count_file_objects() >= $this->max_number_of_files && !is_file($this->get_upload_path($file_name))) { // Ignore additional chunks of existing files:
            return $this->get_error_message('max_number_of_files');
        }

        return '';
    }
}

