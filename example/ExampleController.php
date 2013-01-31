<?php
/**
 * Пример контроллера, в котором к модели добавляется поведение для защиты от спама.
 *
 * @copyright  Copyright (c) 2013 Kuponator.ru
 * @author     Yaroslav Usatikov <yaroslav@usatikov.com>
 */
class ExampleController extends Controller
{

    public function actionIndex()
    {
        $model = new ExampleModel();
        $model->attachBehavior('cleanWeb', array(
            'class' => 'YandexCleanWebBehaviour',
            'apiKey' => '{YANDEX API KEY}',
        ));

        if (isset($_POST['ExampleModel'])) {
            $model->attributes = $_POST['ExampleModel'];
            $model->cleanWebValues = array(
                'body' => $model->text,
            );
            $model->validate();
        }
        else {
            $model->initCleanWeb();
        }

        $this->render('example_view', array(
            'model' => $model,
        ));
    }

}
