AntiSpamBehaviour
===============

Behaivior (поведение) к моделям YII Framework для защиты от спама с помощью API Яндекса "Чистый Веб".

Документация по API: http://api.yandex.ru/cleanweb/doc/dg/concepts/about.xml

-----------------------------

### Пример использования

#### Контроллер

```php
  $model = new SomeModel();
  $model->attachBehavior('antiSpam', array(
      'class' => 'AntiSpamBehaviour',
      'apiKey' => '{ключ}',
  ));

  if (isset($_POST['SomeModel'])) {
     $model->attributes = $_POST['SomeModel'];
     $model->antiSpamValues = array(
        'body' => $model->text,
     );
     $model->validate();
  }
  else {
     $model->initAntiSpam();
  }
```

#### Представление

В представлении проверять необходимость отображения CAPTCHA с помощью ($model->getCaptcha() == null).

**Отображение CAPTCHA**:

```html
<img src="<?= $model->getCaptcha() ?>" alt="Защита от спама" width="200" height="60" />
```

Более подробный пример можно найти в каталоге **example**.
