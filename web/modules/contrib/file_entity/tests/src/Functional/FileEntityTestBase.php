<?php

namespace Drupal\Tests\file_entity\Functional;

use Drupal\Core\Config\Config;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\file_entity\Entity\FileType;
use Drupal\file_entity\Entity\FileEntity;
use Drupal\Tests\BrowserTestBase;

/**
 * Base class for file entity tests.
 */
abstract class FileEntityTestBase extends BrowserTestBase {

  /**
   * @var array
   */
  public static $modules = array('file_entity');

  /**
   * File entity config.
   *
   * @var Config
   */
  protected $config;

  /**
   * @var FileInterface[][]
   */
  protected $files = array();

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->config = $this->config('file_entity.settings');
  }

  /**
   * Set up some sample text and image files.
   */
  protected function setUpFiles($defaults = array()) {
    // Populate defaults array.
    $defaults += array(
      'uid' => 1,
      'status' => FILE_STATUS_PERMANENT,
    );

    $types = array('text', 'image');
    foreach ($types as $type) {
      foreach ($this->drupalGetTestFiles($type) as $file) {
        foreach ($defaults as $key => $value) {
          $file->$key = $value;
        }
        $file = File::create((array) $file);
        $file->save();
        $this->files[$type][] = $file;
      }
    }
  }

  /**
   * Creates a test file type.
   *
   * @param array $overrides
   *   (optional) An array of values indexed by FileType property names.
   *
   * @return \Drupal\Core\Entity\EntityInterface|static
   */
  protected function createFileType($type = array()) {
    $type += array(
      'id' => strtolower($this->randomMachineName()),
      'label' => 'Test',
      'mimetypes' => array('image/jpeg', 'image/gif', 'image/png', 'image/tiff'),
    );
    $entity = FileType::create($type);
    $entity->save();
    return $entity;
  }

  /**
   * Helper for testFileEntityPrivateDownloadAccess() test.
   *
   * Defines several cases for accesing private files.
   *
   * @return array
   *   Array of associative arrays, each one having the next keys:
   *   - "message" string with the assertion message.
   *   - "permissions" array of permissions or NULL for anonymous user.
   *   - "expect" expected HTTP response code.
   *   - "owner" Optional boolean indicating if the user is a file owner.
   */
  protected function getPrivateDownloadAccessCases() {
    return array(
      array(
        'message' => "File owners cannot download their own files unless they are granted the 'view own private files' permission.",
        'permissions' => array(),
        'expect' => 403,
        'owner' => TRUE,
      ),
      array(
        'message' => "File owners can download their own files as they have been granted the 'view own private files' permission.",
        'permissions' => array('view own private files'),
        'expect' => 200,
        'owner' => TRUE,
      ),
      array(
        'message' => "Anonymous users cannot download private files.",
        'permissions' => NULL,
        'expect' => 403,
      ),
      array(
        'message' => "Authenticated users cannot download each other's private files.",
        'permissions' => array(),
        'expect' => 403,
      ),
      array(
        'message' => "Users who can view public files are not able to download private files.",
        'permissions' => array('view files'),
        'expect' => 403,
      ),
      array(
        'message' => "Users who bypass file access can download any file.",
        'permissions' => array('bypass file access'),
        'expect' => 200,
      ),
    );
  }

  /**
   * Retrieves a sample file of the specified type.
   */
  function getTestFile($type_name, $size = NULL) {
    // Get a file to upload.
    $file = current($this->drupalGetTestFiles($type_name, $size));

    // Add a filesize property to files as would be read by file_load().
    $file->filesize = filesize($file->uri);

    return $file;
  }

  /**
   * Get a file from the database based on its filename.
   *
   * @param $filename
   *   A file filename, usually generated by $this->randomMachineName().
   * @param $reset
   *   (optional) Whether to reset the internal file_load() cache.
   *
   * @return \Drupal\file\FileInterface
   *   A file object matching $filename.
   */
  function getFileByFilename($filename, $reset = FALSE) {
    $files = entity_load_multiple_by_properties('file', array('filename' => $filename), $reset);
    // Load the first file returned from the database.
    $returned_file = reset($files);
    return $returned_file;
  }

  /**
   * Create a file in the database and on disk, asserting its success.
   *
   * @param array $values
   *   (optional) Values of the new file. Default values are supplied.
   *
   * @return FileEntity
   *   The newly created file.
   */
  protected function createFileEntity($values = array()) {
    // Populate defaults array.
    $values += array(
      // Prefix filename with non-latin characters to ensure that all
      // file-related tests work with international filenames.
      'filename' => 'Файл для тестирования ' . $this->randomMachineName(),
      'filemime' => 'text/plain',
      'uid' => 1,
      'created' => REQUEST_TIME,
      'status' => FILE_STATUS_PERMANENT,
      'contents' => "file_put_contents() doesn't seem to appreciate empty strings so let's put in some data.",
      'scheme' => file_default_scheme(),
    );

    $values['uri'] = $values['scheme'] . '://' . $values['filename'];

    file_put_contents($values['uri'], $values['contents']);
    $this->assertTrue(is_file($values['uri']), t('The test file exists on the disk.'), 'Create test file');

    $file = FileEntity::create($values);

    // Save the file and assert success.
    $result = $file->save();
    $this->assertIdentical(SAVED_NEW, $result, t('The file was added to the database.'), 'Create test file');

    return $file;
  }

}