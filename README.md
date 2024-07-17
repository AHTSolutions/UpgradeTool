# Upgrade tool

This is a simple tool for searching and investigation changes in your code
after Magento upgrade to new version. 

The first aspect is investigating changes in Magento code. 
If your classes are used in some rewrites of origin Magento clasess
or implement Magento interfeces and those have been changed,
this tool will help to find them.

Second aspect is investigating dependencies in your code.
If your code depends on origin Magento code and it was changed,
this tool will help to find this code.

## Usage

First of all it requires preparation of project for upgrade and investigation:
* Copy current `vendor` dir to new place outside the project
* Upgrade project to required Magento version
* Install this tool in the project

### Setup

Add this configuration to `composer.json` file:
```json
  "require-dev": {
    "ahtsolutions/upgradetool": "dev-master",
  },
  "repositories": {
      "ahtsolutions/upgradetool": {
          "type": "git",
          "url": "https://github.com/AHTSolutions/UpgradeTool.git"
      }
  }
```
After that run command `composer update`.

### Command execution

From project dir run command `./vendor/bin/mg-upgrade-tool ...` with required options.

### Tool options

Here you can see the list of these options after running tool with `--help` parameter - `mg-upgrade-tool --help`

Options:
* `-d` or `--previous_vendor` - absolute path for previous vendor what you saved before Magento version upgrade, it's required option
* `-r` or `--result_file` - absolute path for saving result, it's required option
* `-f` or `--format` - result file format, there is only one format for now(`txt`)
* `-p` or `--vendor_name` - modules vendor name for dependency investigation (usually it's directory name in `app/code`), it's required option
* `-m` or `--compare_command` - command for preparing line in result file that can be used for changed files in the future comparison
* `-a` or `--used_areas` - used area code for dependency investigation
* `-c` or `--conf` - path to json file where you can determine every previous option (Example: `upgrade-config.json.dist`)

You can ignore required options if you determine them in config file and use it for command execution. 

## One of using example

1. Copy `vendor` dir to custom place, for example - `/var/www/previous_vendor`
2. Update Magento version in `composer.json` and add UpdateTool to the project dependency (look at **Setup** step)
3. Run `composer update`
4. Go to the current project directory(`/var/www/project_name`) and prepare config file(`upgrade-config.json`):
    ``` json
   {
      "previous_vendor": "/var/www/previous_vendor",
      "result_file": "/var/www/project_name/var/dep_result.txt",
      "vendor_name": "YOUR_VENDOR",
      "used_areas": "all",
      "compare_command": "diff"
   } 
    ```
5. Run command `./vendor/bin/mg-upgrade-tool -c upgrade-config.json` and check result in the file `/var/www/project_name/var/dep_result.txt`.
