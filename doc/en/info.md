
## auto translated !!!!!!!!!!!!!!!!

Extension "all"

This extension provides a simple functions and settings globally available that do not occur in other extensions or with no real extension require.

Exists for the central extension under "backend / extensions / all /" may still be a "sister-Extension" under "projects / [project directory] / extensions / all / '.
Configuration

Here are some configurations for the backend are stored. The values ​​are encapsulated in the array so that the central values ​​with values ​​in the respective project configuration under project / extensions / all / can be überschriben config / config.php.

    theme: The default theme in the backend
    autologous: automatic login without username / password (useful for local use)
    wizards: ... 

Example

 "theme": ["humanity"], "wizards": [], "autolog": [0] 

Hooks

In the "hooks.php" file, the central back-end hooks are stored. They "listen" to certain actions in the backend and run before or after the operation.
Use

In the file "hooks.php" can be used to check which hook functions are already available globally.

Central Hooks are available to all projects available and should be applied only by the maintainers of the overall system (hence no write permissions for "hooks.php")!

Project-related hooks can be used as project extension to create and administrate. 
