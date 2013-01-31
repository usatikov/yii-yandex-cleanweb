<?php
/**
 * Пример модели, к которой добавляется поведение для защиты от спама.
 *
 * @copyright  Copyright (c) 2013 Kuponator.ru
 * @author     Yaroslav Usatikov <ys@kuponator.ru>
 */
class ExampleModel extends CFormModel
{
    public $name;
    public $text;
    public $captcha;

    public function rules()
    {
        return array(
            array('name, text', 'required'),
            array('name, text, captcha', 'safe'),
        );
    }
}
