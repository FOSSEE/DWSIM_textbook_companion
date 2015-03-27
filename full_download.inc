<?php
// $Id$

function textbook_companion_download_full_chapter()
{
  $chapter_id = arg(2);
  $root_path = textbook_companion_path();
  $APPROVE_PATH = 'APPROVED/';
  $PENDING_PATH = 'PENDING/';

  /* get example data */
  $chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $chapter_id);
  $chapter_data = db_fetch_object($chapter_q);
  $CH_PATH = 'CH' . $chapter_data->number . '/';

  /* zip filename */
  $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';

  /* creating zip archive on the server */
  $zip = new ZipArchive;
  $zip->open($zip_filename, ZipArchive::CREATE);

  /* approved examples */
  $example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 1", $chapter_id);
  while ($example_row = db_fetch_object($example_q))
  {
    $EX_PATH = 'EX' . $example_row->number . '/';
    $example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);
    $example_dependency_files_q = db_query("SELECT * FROM {textbook_companion_example_dependency} WHERE example_id = %d", $example_row->id);
    while ($example_files_row = db_fetch_object($example_files_q))
    {
      $zip->addFile($root_path . $example_files_row->filepath, $APPROVE_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
    }
    /* dependency files */
    while ($example_dependency_files_row = db_fetch_object($example_dependency_files_q))
    {
      $dependency_file_data = db_fetch_object(db_query("SELECT * FROM {textbook_companion_dependency_files} WHERE id = %d LIMIT 1", $example_dependency_files_row->dependency_id));
      if ($dependency_file_data)
        $zip->addFile($root_path . $dependency_file_data->filepath, $APPROVE_PATH . $CH_PATH . $EX_PATH . 'DEPENDENCIES/' . $dependency_file_data->filename);
    }
  }

  /* unapproved examples */
  $example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 0", $chapter_id);
  while ($example_row = db_fetch_object($example_q))
  {
    $EX_PATH = 'EX' . $example_row->number . '/';
    $example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);
    $example_dependency_files_q = db_query("SELECT * FROM {textbook_companion_example_dependency} WHERE example_id = %d", $example_row->id);
    while ($example_files_row = db_fetch_object($example_files_q))
    {
      $zip->addFile($root_path . $example_files_row->filepath, $PENDING_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
    }
    /* dependency files */
    while ($example_dependency_files_row = db_fetch_object($example_dependency_files_q))
    {
      $dependency_file_data = db_fetch_object(db_query("SELECT * FROM {textbook_companion_dependency_files} WHERE id = %d LIMIT 1", $example_dependency_files_row->dependency_id));
      if ($dependency_file_data)
        $zip->addFile($root_path . $dependency_file_data->filepath, $PENDING_PATH . $CH_PATH . $EX_PATH . 'DEPENDENCIES/' . $dependency_file_data->filename);
    }
  }

  $zip_file_count = $zip->numFiles;
  $zip->close();

  if ($zip_file_count > 0)
  {
    /* download zip file */
    header('Content-Type: application/zip');
    header('Content-disposition: attachment; filename="CH' . $chapter_data->number . '.zip"');
    header('Content-Length: ' . filesize($zip_filename));
    header("Content-Transfer-Encoding: binary");
    header('Expires: 0');
    header('Pragma: no-cache');
    ob_end_flush();
    ob_clean();
    flush();

    readfile($zip_filename);
    unlink($zip_filename);
  } else {
    drupal_set_message("There are no examples in this chapter to download", 'error');
    drupal_goto('code_approval/bulk');
  }
}

function textbook_companion_download_full_book()
{
  $book_id = arg(2);
  $root_path = textbook_companion_path();
  $APPROVE_PATH = 'APPROVED/';
  $PENDING_PATH = 'PENDING/';
  /* get example data */
  $book_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $book_id);
  $book_data = db_fetch_object($book_q);
  $BK_PATH = $book_data->book . '/';

  /* zip filename */
  $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';

  /* creating zip archive on the server */
  $zip = new ZipArchive;
  $zip->open($zip_filename, ZipArchive::CREATE);

  /* approved examples */
  $chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d", $book_id);
  while ($chapter_row = db_fetch_object($chapter_q))
  {
    $CH_PATH = 'CH' . $chapter_row->number . '/';
    $example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 1", $chapter_row->id);
    while ($example_row = db_fetch_object($example_q))
    {
      $EX_PATH = 'EX' . $example_row->number . '/';
      $example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);
      $example_dependency_files_q = db_query("SELECT * FROM {textbook_companion_example_dependency} WHERE example_id = %d", $example_row->id);
      while ($example_files_row = db_fetch_object($example_files_q))
      {
        $zip->addFile($root_path . $example_files_row->filepath, $BK_PATH . $APPROVE_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
      }
      /* dependency files */
      while ($example_dependency_files_row = db_fetch_object($example_dependency_files_q))
      {
        $dependency_file_data = db_fetch_object(db_query("SELECT * FROM {textbook_companion_dependency_files} WHERE id = %d LIMIT 1", $example_dependency_files_row->dependency_id));
        if ($dependency_file_data)
          $zip->addFile($root_path . $dependency_file_data->filepath, $BK_PATH . $APPROVE_PATH . $CH_PATH . $EX_PATH . 'DEPENDENCIES/' . $dependency_file_data->filename);
      }
    }

    /* unapproved examples */
    $example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 0", $chapter_row->id);
    while ($example_row = db_fetch_object($example_q))
    {
      $EX_PATH = 'EX' . $example_row->number . '/';
      $example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);
      $example_dependency_files_q = db_query("SELECT * FROM {textbook_companion_example_dependency} WHERE example_id = %d", $example_row->id);
      while ($example_files_row = db_fetch_object($example_files_q))
      {
        $zip->addFile($root_path . $example_files_row->filepath, $BK_PATH . $PENDING_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
      }
      /* dependency files */
      while ($example_dependency_files_row = db_fetch_object($example_dependency_files_q))
      {
        $dependency_file_data = db_fetch_object(db_query("SELECT * FROM {textbook_companion_dependency_files} WHERE id = %d LIMIT 1", $example_dependency_files_row->dependency_id));
        if ($dependency_file_data)
          $zip->addFile($root_path . $dependency_file_data->filepath, $BK_PATH . $PENDING_PATH . $CH_PATH . $EX_PATH . 'DEPENDENCIES/' . $dependency_file_data->filename);
      }
    }
  }

  $zip_file_count = $zip->numFiles;
  $zip->close();

  if ($zip_file_count > 0)
  {
    /* download zip file */
    header('Content-Type: application/zip');
    header('Content-disposition: attachment; filename="' . $book_data->book . '.zip"');
    header('Content-Length: ' . filesize($zip_filename));
    //header("Content-Transfer-Encoding: binary");
    //header('Expires: 0');
    //header('Pragma: no-cache');
    //ob_end_flush();
    ob_clean();
    //flush();
    readfile($zip_filename);
    unlink($zip_filename);
  } else {
    drupal_set_message("There are no examples in this book to download", 'error');
    drupal_goto('code_approval/bulk');
  }
}

