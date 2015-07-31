<?

namespace controllers;

/**
 * Class Crud
 * базовый класс контроллера в стиле CRUD для backbone.js
 *
 * @package controllers
 */
abstract class Crud extends Controller {
	/* @var string - класс модели которую использует контроллер */
	static $model_class = 'models\Model';

	/* @var \models\Model */
	protected $model = null;

	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->model = new static::$model_class();
	}

	/**
	 * отрендерить интерфейс
	 * @param null $params
	 * @return string
	 */
	public function show($params=null) {
		return $this->templater(__FUNCTION__);
	}

	/**
	 * экщен для чтения, в параметрах можно передать id записи и получить ее, либо получить весь список записей
	 * @param null $params
	 * @return string
	 */
	public function read($params=null) {
		if (!isset($params['id'])) return $this->model->jsonList();

		/* @var $model \models\Model */
		if ($model = $this->model->findByPk($params['id'])) return $model->toJson();

		header('Status: 404 Not Found');
		return json_encode([
			'title'  => 'Ошибки при получении данных с сервера<br>'.__METHOD__,
			'header' => 'При получении данных произошли ошибки',
			'errors' => ['Запись номер '.$params['id'].'Не найдена! Пожалуйста обновите страницу.']
		]);
	}

	/**
	 * экщен для создани новой записи, данные берет из POST запроса в виде json
	 * @param null $params
	 * @return string
	 */
	public function create($params=null) {
		$save = $this->model->insert(static::getJsonRequest());
		if ($save !== false) return json_encode($save);

		header('Status: 400 Bad Request');
		return json_encode([
			'title'  => 'Ошибки при создании записи на сервере<br>'.__METHOD__,
			'header' => 'Ошибки валидации по следующим полям',
			'errors' => array_keys(array_filter($this->model->validate(), 'is_empty'))
		]);
	}

	/**
	 * экщен для изменения записи, данные берет из PUT запроса в виде json, в параметрах обязательно наличие id
	 * @param null $params
	 * @return string
	 */
	public function update($params=null) {
		/* @var $model \models\Model */
		if (isset($params['id']) && $model = $this->model->findByPk($params['id'])) {
			$save = $model->update(static::getJsonRequest());
			if ($save !== false) return json_encode($save);

			header('Status: 400 Bad Request');
			return json_encode([
				'title'  => 'Ошибки при сохранении записи на сервере<br>'.__METHOD__,
				'header' => 'Ошибки валидации по следующим полям',
				'errors' => array_keys(array_filter($model->validate(), 'is_empty'))
			]);
		}

		header('Status: 404 Not Found');
		return json_encode([
			'title'  => 'Ошибки при сохранении записи на сервере<br>'.__METHOD__,
			'header' => 'При сохранении записи произошли ошибки',
			'errors' => ['Запись номер '.$params['id'].'Не найдена! Пожалуйста обновите страницу.']
		]);
	}

	/**
	 * экщен для удаления записи, в параметрах обязательно наличие id
	 * @param null $params
	 * @return string
	 */
	public function delete($params=null) {
		if (isset($params['id']) && $this->model->deleteByPk($params['id'])) return json_encode([]);

		header('Status: 404 Not Found');
		return json_encode([
			'title' => 'Ошибка при удалении записи на сервере<br>'.__METHOD__,
			'header' => 'При удалении записи произошли ошибки',
			'errors' => ['Запись номер '.$params['id'].'Не найдена! Пожалуйста обновите страницу.']
		]);
	}
}

?>