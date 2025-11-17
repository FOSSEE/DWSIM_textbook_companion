<?php
 
namespace Drupal\textbook_companion\Services;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Database\Database;
use Drupal\Core\DrupalKernel;
use Drupal\user\Entity\User;

class TextbookCompanionGlobalFunction{

function _list_of_books($category_default_value) {
  $book_titles = [
    0 => 'Please select ...',
  ];

  $connection = \Drupal::database();

  // Main query on textbook_companion_preference table.
  $query = $connection->select('textbook_companion_preference', 'tcp');
  $query->fields('tcp');

  // Apply filters.
  $query->condition('tcp.category', $category_default_value);
  $query->condition('tcp.approval_status', 1);

  // Subquery on textbook_companion_proposal table.
  $subquery = $connection->select('textbook_companion_proposal', 'tcpp');
  $subquery->fields('tcpp', ['id']);
  $subquery->condition('tcpp.proposal_status', 3);

  $query->condition('tcp.proposal_id', $subquery, 'IN');

  // Order by book ASC.
  $query->orderBy('tcp.book', 'ASC');

  // Execute query.
  $result = $query->execute();

  foreach ($result as $book_titles_data) {
    $book_titles[$book_titles_data->id] =
      $book_titles_data->book . ' (Written by ' . $book_titles_data->author . ')';
  }

  return $book_titles;
}

function _list_of_examples($chapter_id = 0) {
  $book_examples = [
    0 => 'Please select...',
  ];

  $connection = \Drupal::database();

  // Main query.
  $query = $connection->select('textbook_companion_example', 'tce');
  $query->fields('tce');
  $query->condition('tce.chapter_id', $chapter_id);
  $query->condition('tce.approval_status', 1);

  // Sorting examples properly (similar to original raw SQL).
  // Equivalent to SUBSTRING_INDEX logic.
  $query->addExpression("CAST(SUBSTRING_INDEX(tce.number, '.', 1) AS UNSIGNED)", 'part1');
  $query->addExpression("CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(tce.number, '.', 2), '.', -1) AS UNSIGNED)", 'part2');
  $query->addExpression("CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(tce.number, '.', -1), '.', 1) AS UNSIGNED)", 'part3');

  $query->orderBy('part1', 'ASC');
  $query->orderBy('part2', 'ASC');
  $query->orderBy('part3', 'ASC');

  // Execute query.
  $result = $query->execute();

  foreach ($result as $book_examples_data) {
    $book_examples[$book_examples_data->id] =
      $book_examples_data->number . ' (' . $book_examples_data->caption . ')';
  }

  return $book_examples;
}

function _list_of_chapters($preference_id = 0) {
  $book_chapters = [
    0 => 'Please select...',
  ];

  $connection = \Drupal::database();

  // Build query.
  $query = $connection->select('textbook_companion_chapter', 'tcc');
  $query->fields('tcc');
  $query->condition('tcc.preference_id', $preference_id);
  $query->orderBy('tcc.number', 'ASC');

  // Execute query.
  $result = $query->execute();

  foreach ($result as $book_chapters_data) {
    $book_chapters[$book_chapters_data->id] =
      $book_chapters_data->number . '. ' . $book_chapters_data->name;
  }

  return $book_chapters;
}

public function textbook_companion_path()
{
    return $_SERVER['DOCUMENT_ROOT'] . base_path() . 'dwsim_uploads/tbc_uploads/';
}
public function textbook_companion_samplecode_path()
{
    return $_SERVER['DOCUMENT_ROOT'] . base_path() . 'dwsim_uploads/tbc_sample_code/';
}

}
