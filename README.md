# Auto Alt Text
This WordPress plugin allows you to automatically generate an Alt Text for images uploaded to the media library.
The plugin is able to use the AI of external services of Azure or Openai to generate an alt text that is as faithful as possible to the content of the image.

## Features
___
This plugin allows you to generate alt texts in the following ways:
- using Azure APIs for computational vision;
- using Openai APIs
- recovering the title of the image
- recovering the title of the article in which the image is uploaded

## Prerequisites
___
Please ensure you have the following tools installed:
- composer
- npm

## Getting started
___
Within your WordPress installation, navigate to the plugin directory and run the following commands:

```bash
git clone git@github.com:valeriomonti/auto-alt-text.git
cd auto-alt-text
composer install
npm install
npm run build
```
In the wp-admin panel activate the Auto Alt Text plugin. 

The plugin settings are found in the Settings -> Auto Alt Text Options menu.

### How it works
Once the plugin has been configured, every time you upload an image to the media library, the alt text field will automatically be filled in based on your preferences.
### Logging
If the generation of the alt text via AI is set, in the event of errors, to avoid blocking the editorial work, the image is loaded anyway but without the alt text being compiled.

However, an error record is printed in the log file with all explanation. 

The logs are saved in the "auto-alt-text" folder located in the project's uploads folder.
