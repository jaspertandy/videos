# Videos Asset Bundle

## Install

Run the following commands:

```
cd src/web/assets/videos/
yarn
```

## Build

Run the following commands:
```
cd src/web/assets/videos/
yarn build
```

## Dev

**config/app.php**
```
<?php

use craft\services\Plugins;
use yii\base\Event;

Event::on(Plugins::class, Plugins::EVENT_AFTER_LOAD_PLUGINS, function() {
     \dukt\videos\Plugin::getInstance()->getVideos()->useDevServer = true;
});
```

Run the following commands:
````
cd src/web/assets/videos/
yarn serve
````