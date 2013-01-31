<?php
/**
 * Поведение для защиты от спама с помощью API Яндекса "Чистый Веб".
 * Документация по API: {@link http://api.yandex.ru/cleanweb/doc/dg/concepts/about.xml}
 *
 * Пример использования.
 *
 * Контроллер:
 *
 *   $model = new SomeModel();
 *   $model->attachBehavior('antiSpam', array(
 *       'class' => 'AntiSpamBehaviour',
 *       'apiKey' => '{ключ}',
 *   ));
 *
 *   if (isset($_POST['SomeModel'])) {
 *      $model->attributes = $_POST['SomeModel'];
 *      $model->antiSpamValues = array(
 *         'body' => $model->text,
 *      );
 *      $model->validate();
 *   }
 *   else {
 *      $model->initAntiSpam();
 *   }
 *
 * В представлении проверять необходимость отображения CAPTCHA с помощью ($model->getCaptcha() == null).
 * Отображение CAPTCHA:
 *
 * <img src="<?= $model->getCaptcha() ?>" alt="Защита от спама" width="200" height="60" />
 *
 * @copyright  Copyright (c) 2013 Kuponator.ru
 * @author     Yaroslav Usatikov <ys@kuponator.ru>
 */
class AntiSpamBehaviour extends CModelBehavior
{
    /** @var string API-ключ. Можно получить на {@link http://api.yandex.ru/cleanweb/form.xml} */
    public $apiKey;

    /** @var string URL для доступа к API */
    public $apiUrl = 'http://cleanweb-api.yandex.ru/1.0/';

    /** @var string тип сообщения, возможные значения: plain, html, bbcode */
    public $type = 'plain';

    /** @var string сообщение об ошибке, если есть подозрение на спам */
    public $spamMsg = 'Подозрение на спам. Необходимо ввести символы с картинки';

    /** @var string сообщение об ошибке, если неверно ввенеды символы CAPTCHA */
    public $captchaMsg = 'Неправильно введены символы с картинки';

    /** @var string автоматически определить IP-адрес отправителя */
    public $detectIp = true;

    /** @var bool Не осуществлять проверку сообщения на спам, сразу требовать ввести код с CAPTCHA  */
    public $onlyCaptcha = false;

    /** @var string к какому атрибуту должно относиться сообщение об ошибке */
    public $attribute = 'captcha';


    /** @var string|null URL изображения CAPTCHA или null, если ввод кода не требуется */
    private $_captchaUrl = null;

    /** @var array значения атрибутов, используемых при проверки на спам */
    private $_values = array();

    /**
     * Если пользователю необходимо ввести код с изображения CAPTCHA, будет возвращён URL с этим изображением.
     * Иначе метод вернёт null.
     *
     * @return string|null URL изображения CAPTCHA или null, если ввод кода не требуется
     */
    public function getCaptcha()
    {
        return $this->_captchaUrl;
    }

    /**
     * Установить значения атрибутов, используемых для проверки на спам:
     *
     * ip - IP-адрес отправителя. Если установлен параметр detectIp, то это значение будет проигнорировано
     * email - Адрес электронной почты отправителя
     * name - Имя отправителя, отображаемое в подписях к сообщениям пользователя на форуме или блоге
     * login - Имя учетной записи пользователя на ресурсе (форуме, хостинге блогов, и т.п.)
     * realname - ФИО пользователя взятые, например, из его регистрационных данных
     * subject - Тема поста
     * body - Содержимое
     * captcha -  Введённый пользователем код с изображения CAPTCHA
     *
     * @param array $values
     */
    public function setAntiSpamValues(array $values)
    {
        $this->_values = $values;
    }

    /**
     * Проверка на спам. Выполняется после валидации модели.
     * Проверяет, является ли сообщение спамом и получает URL изображения CAPTCHA.
     */
    public function afterValidate($event)
    {
        if ($this->owner->hasErrors()) {

            return;
        }

        $session = Yii::app()->session;

        if ($session->contains('antispam_msg_id') && $session->contains('antispam_captcha_id')) {
            if ($this->_captchaCheck() == false) {
                $this->_captchaInit($session->get('antispam_msg_id'));
                $this->owner->addError('captcha', $this->captchaMsg);
            }

            return;
        }
        elseif ($this->onlyCaptcha) {
            $this->owner->addError('captcha', $this->captchaMsg);

            return;
        }

        $msgCheck = $this->_msgCheck();

        if ($msgCheck['isSpam']) {
            $this->_captchaInit($msgCheck['id']);
            $this->owner->addError('captcha', $this->spamMsg);
        }
    }

    /**
     * Открытие сессии. Выполняется после создания объекта.
     */
    public function afterConstruct($event)
    {
        Yii::app()->session->open();
    }

    /**
     * Инициализация сессии.
     * Этот метод должен быть вызван из контроллера перед отображением формы.
     */
    public function initAntiSpam()
    {
        $this->_cleanSession();

        if ($this->onlyCaptcha) {
            $this->_captchaInit();
        }
    }

    /**
     * Проверка сообщения на спам
     *
     * @return array массив {id => %ID сообщения%, isSpam => %есть ли подозрение на спам%}
     */
    private function _msgCheck()
    {
        $fields = array('email', 'name', 'login', 'realname', 'subject', 'body');
        $data = array();

        if ($this->detectIp && isset($_SERVER['REMOTE_ADDR'])) {
            $data['ip'] = $_SERVER['REMOTE_ADDR'];
        }
        else {
            $fields[] = 'ip';
        }

        foreach ($fields as $field) {
            if (in_array($field, array('subject', 'body'))) {
                $dataField = $field . '-' . $this->type;
            }
            else {
                $dataField = $field;
            }

            if (isset($this->_values[$field])) {
                $data[$dataField] = $this->_values[$field];
            }
            elseif (isset($this->owner->$field) && $this->owner->$field != null) {
                $data[$dataField] = $this->owner->$field;
            }
        }

        $response = $this->_makeRequest('check-spam', $data, true);

        return array(
            'id' => $response->id,
            'isSpam' => ($response->text['spam-flag'] == 'yes'),
        );
    }

    /**
     * Проверка кода CAPTCHA, введённого пользователем
     *
     * @return bool
     */
    private function _captchaCheck()
    {
        $session = Yii::app()->session;

        $value = $this->_captchaValue();

        if (!$value) {

            return false;
        }

        $response = $this->_makeRequest('check-captcha', array(
            'id' => $session->get('antispam_msg_id'),
            'captcha' => $session->get('antispam_captcha_id'),
            'value' => $value,
        ));

        if (isset($response->ok)) {
            $this->_cleanSession();

            return true;
        }

        return false;
    }

    /**
     * @return string введённый пользователем код с изображниея CAPTCHA
     */
    private function _captchaValue()
    {
        if (isset($this->_values['captcha'])) {
            return $this->_values['captcha'];
        }

        if (isset($this->owner->captcha)) {
            return $this->owner->captcha;
        }

        return null;
    }

    /**
     * Получение URL изображения CAPTCHA
     *
     * @param string|null $msgId ID сообщения, если производилась его проверка на спам
     */
    private function _captchaInit($msgId = null)
    {
        $ch = curl_init();

        $captchaResponse = $this->_makeRequest('get-captcha', array(
            'id' => $msgId,
        ));

        curl_close($ch);

        $session = Yii::app()->session;
        $session->add('antispam_msg_id', (string)$msgId);
        $session->add('antispam_captcha_id', (string)$captchaResponse->captcha);

        $this->_captchaUrl = (string)$captchaResponse->url;
    }

    /**
     * Выполнение запроса к API
     *
     * @param string $method название метода API
     * @param array $params параметры запроса
     * @param bool $isPost требуется ли POST-запрос
     * @return SimpleXMLElement результат запроса
     */
    private function _makeRequest($method, array $params, $isPost = false)
    {
        $query = http_build_query(CMap::mergeArray(array(
            'key' => $this->apiKey,
        ), $params));

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_POST, true);

        $url = $this->apiUrl . $method;

        if ($isPost) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        }
        else {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            $url .= '?' . $query;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);

        $response = new SimpleXMLElement(curl_exec($ch));
        curl_close($ch);

        return $response;
    }

    /**
     * Очистка переменных сессии
     */
    private function _cleanSession()
    {
        $session = Yii::app()->session;
        $session->remove('antispam_msg_id');
        $session->remove('antispam_captcha_id');
    }

}
