# Zonemaster WordPress Theme
### IIS Zonemaster
This theme is made by IIS (The Internet Foundation In Sweden)

It's intent is to be a starting point for a WordPress based front end that connects to Zonemaster backend server and serves as a GUI.

The theme is Open Source and you are free to change it however you want. IIS Zonemaster is not deployment ready from these source files, you will need to compile your own version.
> Note. IIS Zonemaster works even if the user does not use javascript in their browser

## How to setup your development environment
### Tech

IIS Zonemaster uses a number of open source projects:
* [npm] - Package installer
* [grunt] - Building javascript and css
* [Foundation] - CSS and Javascript framework
* [jQuery]

### Installation
Download repository to your local environment.
In terminal, cd to your theme folder and into zonemaster-iis-frontend.
Run npm install to download all needed packages
```sh
$ npm install
```
When ready, build your first version of css and javascript
```sh
$ grunt
```

### Languages
The theme is translation ready. Used in a only English or only Swedish WordPress installation you don't need to do anything.

If you want to provide an alternative language you should install the WordPress plugin [Polylang]. For example you could then activate both English & Swedish and the user will get a language menu to switch language with.

### Options / Settings
If you already have a license for the plugin [ACF pro] (Advanced Custom Fields pro version) - or if you want to get a license - then install it. This will let you set all options that is used by the theme to handle backend server connections. (In /wp-admin/ you'll find a menu "API-server")

**But** you could do the same settings in the file
```file
{theme-folder}/zonemaster/class-zonemaster-settings.php
````
In this file you'll find examples on how to add your options.

### Changing primary colors
Our theme is very much branded for IIS. To get rid of the yellow and blue colors and add your own find the sass-file
```file
{theme-folder}/scss/_settings.scss
```
In this file, find around line 82:
```
$primary-color: $blue-color; // set to your own base colors
$secondary-color: $yellow-color; // set to your own base colors
```
Declare your own replacement for $blue-color and $yellow-color

Compile in terminal with
```
$ grunt
```
and see if you like the result

### Deploy
Internally we use rsync to move compiled files to our stage and production environment.

Open Gruntfile.js and look for the code
```code
rsync: {
````
In this section you will have to modify the code to suit your environment if you want to use rsync.

**For example** - rsync from terminal to stage environment:
```ssh
$ grunt deploy:stage
```
This will build your files and move them to your server

   [git-repo-url]: <https://github.com/sewebb/zonemaster-iis-frontend>
   [jQuery]: <https://jquery.com>
   [npm]: <https://www.npmjs.com/>
   [grunt]: <http://gruntjs.com/>
   [Foundation]: <http://foundation.zurb.com/>
   [Polylang]:<https://wordpress.org/plugins/polylang/>
   [ACF pro]: <https://www.advancedcustomfields.com/pro/>

