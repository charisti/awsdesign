<?php

/**
 * @file
 * Contains a Subtheme generation script.
 */

define('GENERATOR_TEMPLATE_FOLDER', 'themes/material_base_subtheme');
define('GENERATOR_PLACEHOLDER', 'THEMENAME');

define('GENERATOR_DEFAULT_THEMENAME', 'material_top');
// The default path should be relative.
define('GENERATOR_DEFAULT_THEMES_PATH', '../../custom');

// Getting the project name from environment variables.
if ($project = getenv('PROJECT_NAME')) {
  define('GENERATOR_THEMENAME', 'material_' . $project);
}
else {
  define('GENERATOR_THEMENAME', GENERATOR_DEFAULT_THEMENAME);
}

/**
 * Main function.
 */
handle_arguments($argv, $argc);

/**
 * Resolve options from command line arguments.
 *
 * Execute generate function with corresponding options.
 *
 * @param array $args
 *   Array of command line arguments.
 * @param int $args_count
 *   Amount of command line arguments.
 */
function handle_arguments(array $args, int $args_count): void {

  // Handling arguments by amount.
  if ($args_count == 1) {
    // No args.
    generate_subtheme();
    exit;
  }
  elseif ($args_count == 2) {
    // 1 argument.
    if ($args[1] == '-h' || $args[1] == '--help' || $args[1] == 'help') {
      // Argument is help calling.
      show_help();
      exit;
    }

    // Check that argument is valid themename.
    if (validate_themename($args[1])) {
      // Executing generation.
      $variables['themename'] = $args[1];
      generate_subtheme($variables);
      exit;
    }

    echo 'Theme name not valid.' . PHP_EOL;
    exit(1);
  }
  elseif ($args_count == 3) {
    // 2 arguments.
    // Check that 1st argument is valid themename.
    if (validate_themename($args[1])) {
      // Check that the 2nd argument is an existing path.
      if ($path = validate_path($args[2])) {
        // Executing generation.
        $variables['themename'] = $args[1];
        $variables['path'] = $path;
        generate_subtheme($variables);
        exit;
      }

      echo 'Path not valid.' . PHP_EOL;
      exit(1);
    }

    echo 'Theme name not valid.' . PHP_EOL;
    exit(1);
  }

  // More than 2 arguments.
  echo 'Too many arguments.' . PHP_EOL;
  exit(1);
}

/**
 * Show a help message.
 */
function show_help(): void {
  echo 'Subtheme generator' . PHP_EOL;
  echo PHP_EOL;
  echo 'Usage: ' . PHP_EOL;
  echo '  php generate.php [themename] [path to themes folder]' . PHP_EOL;
  echo PHP_EOL;
  echo 'Examples: ' . PHP_EOL;
  echo '  php generate.php' . PHP_EOL;
  echo '  php generate.php westeros' . PHP_EOL;
  echo '  php generate.php westeros "../../custom"' . PHP_EOL;
  echo '  php generate.php westeros "/var/www/html/web/themes/custom"' . PHP_EOL;
  exit;
}

/**
 * Validate theme name.
 *
 * @param string $name
 *   Name for validating.
 *
 * @return bool
 *   TRUE if name valid,
 *   FALSE if name not valid.
 */
function validate_themename(string $name): bool {
  if (preg_match('/^[a-z0-9_]+$/', $name)) {
    return TRUE;
  }

  echo 'Theme name should contain only lowercase alphanumeric characters and underscores.' . PHP_EOL;

  return FALSE;
}

/**
 * Validate path.
 *
 * @param string $path
 *   Path for validating.
 *
 * @return string|false
 *   Absolute path if a path is valid,
 *   FALSE if the path is not valid.
 */
function validate_path(string $path): string|false {
  // @todo Handle Windows absolute paths.
  if (str_starts_with($path, '/')) {
    // Absolute path.
    if (is_dir($path)) {
      return realpath($path);
    }

    echo '"' . $path . '" is not a valid path to folder.' . PHP_EOL;

    return FALSE;
  }

  // Relative path.
  if (is_dir(__DIR__ . DIRECTORY_SEPARATOR . $path)) {
    return realpath(__DIR__ . DIRECTORY_SEPARATOR . $path);
  }

  echo '"' . __DIR__ . DIRECTORY_SEPARATOR . $path . '" is not a valid path to folder.' . PHP_EOL;

  return FALSE;
}

/**
 * Generate subtheme.
 *
 * @param array $variables
 *   Array of arguments.
 */
function generate_subtheme(array $variables = []): void {
  $themename = $variables['themename'] ?? GENERATOR_THEMENAME;

  if (isset($variables['path'])) {
    // Already valid path.
    $path = $variables['path'];
  }
  else {
    // Checking default path is valid.
    $path = __DIR__ . DIRECTORY_SEPARATOR . GENERATOR_DEFAULT_THEMES_PATH;
    if (!is_dir($path)) {
      // Creating the default path folder.
      if (!mkdir($path)) {
        echo 'Can not create "' . $path . '".' . PHP_EOL;
        exit(1);
      }
    }
    // Providing a valid path.
    $path = realpath($path);
  }

  $theme_path = $path . DIRECTORY_SEPARATOR . $themename;

  // Copy the template folder to themes folder.
  copy_template_folder($theme_path);

  // Rename files containing placeholder.
  rename_template_files($theme_path, $themename);

  // Replace placeholders in files content.
  replace_template_placeholders($theme_path, $themename);

  // Unhide the theme and update human name.
  update_theme_info($theme_path, $themename);

  echo 'Theme "' . $themename . '" was successfully generated.' . PHP_EOL;
  exit;
}

/**
 * Copy the template folder.
 *
 * @param string $destination
 *   Path to the new theme folder.
 *
 * @return true
 *   Result of executing.
 */
function copy_template_folder(string $destination): true {
  return copy_folder(__DIR__ . DIRECTORY_SEPARATOR . GENERATOR_TEMPLATE_FOLDER, $destination);
}

/**
 * Recursively copy folder.
 *
 * @param string $source
 *   Path to source.
 * @param string $destination
 *   Path to destination.
 *
 * @return true
 *   TRUE if success.
 */
function copy_folder(string $source, string $destination): true {
  // Getting folder content.
  if (!$folder = opendir($source)) {
    echo 'Can not open "' . $source . '".' . PHP_EOL;
    exit(1);
  }

  // Check for destination folder already exist.
  if (is_dir($destination)) {
    echo 'Destination folder "' . $destination . '" already exists, skipping.' . PHP_EOL;
    // Not an error.
    exit;
  }

  // Creating destination folder.
  if (!mkdir($destination)) {
    echo 'Can not create "' . $destination . '".' . PHP_EOL;
    exit(1);
  }

  while (FALSE !== ($file = readdir($folder))) {
    if (($file != '.') && ($file != '..')) {

      $source_file = $source . DIRECTORY_SEPARATOR . $file;
      $destination_file = $destination . DIRECTORY_SEPARATOR . $file;

      if (is_dir($source_file)) {
        copy_folder($source_file, $destination_file);
      }
      else {
        // Copying.
        if (!copy($source_file, $destination_file)) {
          echo 'Can not copy "' . $source_file . '" to "' . $destination_file . '".' . PHP_EOL;
          exit(1);
        }
      }

    }
  }

  closedir($folder);

  return TRUE;
}

/**
 * Recursively rename template files.
 *
 * @param string $path
 *   Path to the theme folder.
 * @param string $themename
 *   Name of the theme.
 *
 * @return true
 *   TRUE if success.
 */
function rename_template_files(string $path, string $themename): true {
  // Getting folder content.
  if (!$folder = opendir($path)) {
    echo 'Can not open "' . $path . '".' . PHP_EOL;
    exit(1);
  }

  while (FALSE !== ($file = readdir($folder))) {
    if (($file != '.') && ($file != '..')) {
      $target_file = $path . DIRECTORY_SEPARATOR . $file;

      if (is_dir($target_file)) {
        rename_template_files($target_file, $themename);
      }
      else {
        // Checking filename has a placeholder.
        if (str_contains($file, GENERATOR_PLACEHOLDER)) {
          // Preparing a new file path.
          $new_file = $path . DIRECTORY_SEPARATOR . str_replace(GENERATOR_PLACEHOLDER, $themename, $file);

          // Renaming.
          if (!rename($target_file, $new_file)) {
            echo 'Can not rename "' . $target_file . '" to "' . $new_file . '".' . PHP_EOL;
            exit(1);
          }
        }
      }

    }
  }

  closedir($folder);

  return TRUE;
}

/**
 * Recursively replace template placeholders.
 *
 * @param string $path
 *   Path to the theme folder.
 * @param string $themename
 *   Name of the theme.
 *
 * @return true
 *   TRUE if success.
 */
function replace_template_placeholders(string $path, string $themename): true {
  // Getting folder content.
  if (!$folder = opendir($path)) {
    echo 'Can not open "' . $path . '".' . PHP_EOL;
    exit(1);
  }

  while (FALSE !== ($file = readdir($folder))) {
    if (($file != '.') && ($file != '..')) {

      $target_file = $path . DIRECTORY_SEPARATOR . $file;

      if (is_dir($target_file)) {
        replace_template_placeholders($target_file, $themename);
      }
      else {
        $file_content = file_get_contents($target_file);
        $replace = 0;
        $file_content = str_replace(GENERATOR_PLACEHOLDER, $themename, $file_content, $replace);
        if ($replace) {
          if (!file_put_contents($target_file, $file_content)) {
            echo 'Can not write to "' . $target_file . '".' . PHP_EOL;
            exit(1);
          }
        }
      }

    }
  }

  closedir($folder);

  return TRUE;
}

/**
 * Update theme properties in THEMENAME.info.yml.
 *
 * Comment out 'hidden: true', create the human readable name.
 *
 * @param string $path
 *   Path to the theme folder.
 * @param string $themename
 *   Name of the theme.
 *
 * @return true
 *   TRUE if success.
 */
function update_theme_info(string $path, string $themename): true {
  $info_file = $path . DIRECTORY_SEPARATOR . $themename . '.info.yml';
  $name = ucfirst(str_replace('_', ' ', $themename));

  $file_content = file_get_contents($info_file);
  // Commenting out 'hidden' property.
  $file_content = str_replace('hidden: true', '# hidden: true', $file_content);
  // Updating theme name.
  $file_content = str_replace('name: Theme name', 'name: ' . $name, $file_content);
  if (!file_put_contents($info_file, $file_content)) {
    echo 'Can not write to "' . $info_file . '".' . PHP_EOL;
    exit(1);
  }

  return TRUE;
}
