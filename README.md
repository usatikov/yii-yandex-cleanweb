YandexCleanWebBehaviour
===============

Behaivior (поведение) к моделям YII Framework для защиты от спама с помощью API Яндекса "Чистый Веб".

Документация по API: http://api.yandex.ru/cleanweb/doc/dg/concepts/about.xml

-----------------------------

### Пример использования

#### Контроллер

```php
  $model = new SomeModel();
  $model->attachBehavior('cleanWeb', array(
      'class' => 'YandexCleanWebBehaviour',
      'apiKey' => '{ключ}',
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
```

#### Представление

В представлении проверять необходимость отображения CAPTCHA с помощью ($model->getCaptcha() == null).

**Отображение CAPTCHA**:

```html
<img src="<?php echo $model->getCaptcha(); ?>" alt="Защита от спама" width="200" height="60" />
```

Более подробный пример можно найти в каталоге **example**.
