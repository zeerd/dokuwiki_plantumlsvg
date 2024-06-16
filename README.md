This plugin integrates [PlantUML](https://plantuml.com) into the [DukuWiki](http://www.dokuwiki.org) wiki engine.
It allows to generate UML graph images from simple description text block.

# Features
* Create any UML graph supported by PlantUML.
* Generated images are SVGs.
* Generated images are cached and regenerated when needed.
* Control the display alignment.
* Works with a PlantUML local installation.

# Local Rendering
Download a last jar from [plantuml.com](https://plantuml.com/en/download) and rename/make a symlink to `plantuml.jar`.

# Image Title
By default, html img title attribute is set to "PlantUML Graph". You can specify your own graph title like this:

    <uml title="This will be the title">
    <uml t=Diagram>

Note: Multiple words need to be placed in double quotes.

# Image Alignment
By default, the alignment is left. You can specify your own graph align like this:

    <uml align=center>

# Thanks to:
* [Andreone](https://github.com/Andreone): author of the origin base code.
* [Willi Schönborn](https://github.com/whiskeysierra): rewrite of the syntax plugin with many additional featuresdddd

