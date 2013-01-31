<?php
/**
 * Пример контроллера, в котором к модели добавляется поведение для защиты от спама.
 *
 * @copyright  Copyright (c) 2013 Kuponator.ru
 * @author     Yaroslav Usatikov <ys@kuponator.ru>
 */
class ExampleController extends Controller
{

    public function actionIndex()
    {
        $model = new ExampleModel();
        $model->attachBehavior('antiSpam', array(
            'class' => 'AntiSpamBehaviour',
            'apiKey' => '{YANDEX API KEY}',
        ));

        if (isset($_POST['ExampleModel'])) {
            $model->attributes = $_POST['ExampleModel'];
            $model->antiSpamValues = array(
                'body' => $model->text,
            );
            $model->validate();
        }
        else {
            $model->initAntiSpam();
        }

        $this->render('example_view', array(
            'model' => $model,
        ));
    }

}
