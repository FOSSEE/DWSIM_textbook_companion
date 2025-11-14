<?php
namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;

class TextbookCompanionRunForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'textbook_companion_run_form';
  }

   
   public function buildForm(array $form, FormStateInterface $form_state) {
    $url_book_pref_id = \Drupal::request()->attributes->get('book_pref_id') ?? 0;
    $category_default_value = 0;

    if ($url_book_pref_id) {
      $query = \Drupal::database()->select('textbook_companion_preference', 't');
      $query->fields('t', ['category']);
      $query->condition('id', $url_book_pref_id);
      $result = $query->execute()->fetchObject();
      $category_default_value = $result ? $result->category : 0;
    }

    // Values from form_state (AJAX) or route attribute defaults.
    $selected_book = (int) ($form_state->getValue('book') ?: $url_book_pref_id);
   // $selected_chapter = (int) $form_state->getValue('chapter');
    //$selected_example = (int) $form_state->getValue('example');


    // var_dump($url_book_pref_id);die;
    // var_dump($selected_book);die;
    // BOOK select (top-level)
    $form['book'] = [
      '#type' => 'select',
      '#title' => $this->t('Title of the book'),
      '#options' => $this->_list_of_books($category_default_value),
      '#default_value' => $selected_book,
      '#ajax' => [
        'callback' => '::ajax_book_changed_callback',
        'wrapper' => 'textbook-book-wrapper',
        'event' => 'change',
      ],
    ];

    // BOOK WRAPPER (contains book info, chapter select & chapter download)
    $form['book_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'textbook-book-wrapper'],
    ];

    if ($selected_book) {
      // Book info markup
      $form['book_wrapper']['book_info'] = [
        '#type' => 'markup',
        '#markup' => $this->_html_book_info($selected_book),
      ];

      // Download Book link
      $form['book_wrapper']['download_book'] = [
        '#type' => 'markup',
        '#markup' => Link::fromTextAndUrl(
          $this->t('Download Book'),
          Url::fromRoute('textbook_companion.download_book', ['book_id' => $selected_book])
        )->toString(),
      ];
$selected_chapter = (int) $form_state->getValue('chapter');

      // Chapter select
      $form['book_wrapper']['chapter'] = [
        '#type' => 'select',
        '#title' => $this->t('Title of the chapter'),
        '#options' => $this->_list_of_chapters($selected_book),
        '#default_value' => $selected_chapter,
        '#ajax' => [
          'callback' => '::ajax_chapter_changed_callback',
          'wrapper' => 'chapter-download-wrapper',
          'event' => 'change',
        ],
      ];

      // Chapter-download wrapper (inside book_wrapper)
      $form['chapter_download'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'chapter-download-wrapper'],
      ];

      if ($selected_chapter) {
        $form['chapter_download']['link'] = [
          '#type' => 'markup',
          '#markup' => Link::fromTextAndUrl(
            $this->t('Download Chapter'),
            Url::fromRoute('textbook_companion.download_chapter', ['chapter_id' => $selected_chapter])
          )->toString(),
        ];
      }
      
    }

    // var_dump($selected_chapter);die;

    $selected_example = (int)$form_state->getValue('examples') ?? 0;

            //  var_dump($selected_example);die;
 
      //  if ($selected_chapter) {

  // Example dropdown
  $form['chapter_download']['examples'] = [
    '#type' => 'select',
    '#title' => $this->t('Name of the example'),
    '#options' => $this->_list_of_examples($selected_chapter, $selected_example),
    '#default_value' => $selected_example,
    '#ajax' => [
      'callback' => '::ajax_example_changed_callback',
      'wrapper' => 'download-example-link-wrapper',
       'event' => 'change',
    ],
  ];


  // Download example + files wrapper
  $form['download_example_wrapper'] = [
    '#type' => 'container',
    '#attributes' => ['id' => 'download-example-link-wrapper'],
  ];
       
  // if ($selected_example) {
if (!empty($selected_example)) {
    // Download link
    $form['download_example_wrapper']['download_example'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl(
        $this->t('Download Example (OpenFOAM code)'),
        Url::fromRoute('textbook_companion.download_example', ['example_id' => $selected_example])
      )->toString(),
    ];
  
  
// $query = \Drupal::database()->select('textbook_companion_example_files', 'f');
// $query->fields('f');
// $query->condition('example_id', $selected_example);
// $result = $query->execute()->fetchAll();

// \Drupal::logger('tbc_debug')->notice('Example ID: @id, Found @count files', [
//   '@id' => $selected_example,
//   '@count' => count($result),
// ]);
// ✅ Example files table rendering
// if (!empty($selected_example)) {
//   $query = \Drupal::database()->select('textbook_companion_example_files', 'f');
//   $query->fields('f');
//   $query->condition('example_id', $selected_example);
//   $files = $query->execute()->fetchAll();

//   // If there are results
//   if (!empty($files)) {
//     $rows = [];
//     foreach ($files as $file) {
//       switch ($file->filetype) {
//         case 'S': $type = 'Source or Main file'; break;
//         case 'R': $type = 'Result file'; break;
//         case 'X': $type = 'xcos file'; break;
//         default:  $type = 'Unknown'; break;
//       }

// $example_files_rows[] = [
//   Link::fromTextAndUrl(
//     $file->filename,
//     Url::fromUserInput('/textbook-companion/download/file/' . $files->id)
//   )->toString(),
//   $type,

// ];

    
//     }

//     // ✅ Wrap table in container
//     $form['download_example_wrapper']['example_files'] = [
//       '#type' => 'fieldset',
//       '#title' => $this->t('List of Example Files'),
//       '#attributes' => ['id' => 'ajax-download-example-files-replace'],
//       'table' => [
//         '#type' => 'table',
//         '#header' => [$this->t('Filename'), $this->t('Type')],
//         '#rows' => $rows,
//               '#attributes' =>[
//         'style' => 'width: 100%;',
//       ],

//         '#empty' => $this->t('No files found for this example.'),
//       ],
//     ];
//   }
// }



if (!empty($selected_example)) {
  $query = \Drupal::database()->select('textbook_companion_example_files', 'f');
  $query->fields('f');
  $query->condition('example_id', $selected_example);
  $files = $query->execute()->fetchAll();

  if (!empty($files)) {
    $rows = [];

    foreach ($files as $file) {
      // Map file type to label
      switch ($file->filetype) {
        case 'S': $type = 'Source or Main file'; break;
        case 'R': $type = 'Result file'; break;
        case 'X': $type = 'xcos file'; break;
        default:  $type = 'Unknown'; break;
      }

      // Ensure you have correct ID field name in your table
      $file_id = $file->id ?? $file->example_file_id ?? NULL;

      // Create link to download file
      $link = Link::fromTextAndUrl(
        $file->filename,
        Url::fromUserInput('/textbook-companion/download/file/' . $file_id)
      )->toRenderable();

      // Add row to the table
      $rows[] = [
        'filename' => ['data' => $link],
        'type' => ['data' => ['#markup' => $type]],
      ];
    }

    // Wrap table in container
    $form['download_example_wrapper']['example_files'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('List of Example Files'),
      '#attributes' => ['id' => 'ajax-download-example-files-replace'],
      'table' => [
        '#type' => 'table',
        '#header' => [$this->t('Filename'), $this->t('Type')],
        '#rows' => $rows,
        '#empty' => $this->t('No files found for this example.'),
        '#attributes' => ['style' => 'width: 100%;'],
      ],
    ];
  }
}

      

       }    return $form;
  }
  // ---------------------------
  // AJAX CALLBACKS
  // ---------------------------

  public function ajax_book_changed_callback(array &$form, FormStateInterface $form_state) {
    return $form['book_wrapper'];
  }

  public function ajax_chapter_changed_callback(array &$form, FormStateInterface $form_state) {
    return $form['chapter_download'];
  }
public function ajax_example_changed_callback(array &$form, FormStateInterface $form_state) {
  // $selected_example = (int) $form_state->getValue('examples') ?? 0;
  // $selected_chapter = (int) $form_state->getValue('chapter') ?? 0;
  $form_state->setRebuild(TRUE);

//   // Rebuild the examples select field with the correct options
  //  $form['examples']['#options'] = $this->_list_of_examples($selected_chapter, $selected_example);

//   // Return the updated download example wrapper

  return $form['download_example_wrapper'];
  // return $form['download_example_wrapper'];
}

  //  ---------------------------
  // HELPER FUNCTIONS
  // ---------------------------

  public function _list_of_books($category_default_value = 0) {
    $book_titles = [0 => $this->t('Please select ...')];
    $connection = \Drupal::database();

    $subquery = $connection->select('textbook_companion_proposal', 'tcp');
    $subquery->fields('tcp', ['id']);
    $subquery->condition('proposal_status', 3);

    $query = $connection->select('textbook_companion_preference', 'tcp');
    $query->fields('tcp', ['id', 'book', 'author']);
    $query->condition('category', $category_default_value);
    $query->condition('approval_status', 1);
    $query->condition('proposal_id', $subquery, 'IN');
    $query->orderBy('book', 'ASC');
    $results = $query->execute()->fetchAll();

    foreach ($results as $book) {
      $book_titles[$book->id] = $book->book . ' (Written by ' . $book->author . ')';
    }

    return $book_titles;
  }

  
  // public function ajax_example_files_callback($example_id) {
  //   if (!$example_id) {
  //       return ['#markup' => ''];
  //   }

  //   $connection = \Drupal::database();
  //   // Assuming the table name is 'textbook_companion_example_file'
  //   $query = $connection->select('textbook_companion_example_file', 'tcef');
  //   $query->fields('tcef', ['id', 'filename', 'filetype']);
  //   $query->condition('example_id', $example_id);
  //   $query->orderBy('filename', 'ASC');
  //   $results = $query->execute()->fetchAll();

  //   if (empty($results)) {
  //       return ['#markup' => ''];
  //   }

  //   $header = [
  //       'filename' => $this->t('Filename'),
  //       'filetype' => $this->t('Type'),
  //   ];

  //   $rows = [];
  //   foreach ($results as $file) {
  //       $example_file_type = $this->t('Unknown');
  //       switch ($file->filetype) {
  //           case 'S':
  //               $example_file_type = $this->t('Source or Main file');
  //               break;
  //           case 'R':
  //               $example_file_type = $this->t('Result file');
  //               break;
  //           case 'X':
  //               $example_file_type = $this->t('xcos file');
  //               break;
  //       }

  //       // Create the linked filename. Adjust the route name if necessary.
  //       $link = Link::fromTextAndUrl(
  //           $file->filename,
  //           Url::fromRoute('textbook_companion.download_file', ['file_id' => $file->id])
  //       )->toString();
        
  //       $rows[] = [
  //           // Ensure rows are simple arrays or use the 'data' structure
  //           // as necessary for your specific Drupal theme.
  //           // Using a simple array for row data is often sufficient.
  //           ['#markup' => $link],
  //           ['#markup' => $example_file_type],
  //       ];
  //   }

  //   return [
  //       '#type' => 'table',
  //       // ✅ REMOVED: Remove the '#caption' element to match the image.
  //       '#header' => $header,
  //       '#rows' => $rows,
  //       // ✅ UPDATED: Add a custom class for styling the table header.
  //       '#attributes' => ['class' => ['textbook-companion-files', 'textbook-companion-example-file-list']],
  //       '#empty' => $this->t('No files found for this example.'),
  //   ];
  // }
  public function _html_book_info($preference_id) {
    $connection = \Drupal::database();

    $query = $connection->select('textbook_companion_proposal', 'proposal');
    $query->leftJoin('textbook_companion_preference', 'preference', 'proposal.id = preference.proposal_id');
    $query->addField('preference', 'book', 'preference_book');
    $query->addField('preference', 'author', 'preference_author');
    $query->addField('preference', 'isbn', 'preference_isbn');
    $query->addField('preference', 'publisher', 'preference_publisher');
    $query->addField('preference', 'edition', 'preference_edition');
    $query->addField('preference', 'year', 'preference_year');
    $query->addField('proposal', 'full_name', 'proposal_full_name');
    $query->addField('proposal', 'faculty', 'proposal_faculty');
    $query->addField('proposal', 'reviewer', 'proposal_reviewer');
    $query->addField('proposal', 'course', 'proposal_course');
    $query->addField('proposal', 'branch', 'proposal_branch');
    $query->addField('proposal', 'university', 'proposal_university');
    $query->condition('preference.id', $preference_id);

    $book_details = $query->execute()->fetchObject();
    if (!$book_details) {
      return '';
    }

    $html_data = '<table style="width:100%;" border="0">';
    $html_data .= '<tr><td style="width:50%;vertical-align:top;">';
    $html_data .= '<strong>About the Book</strong><ul>';
    $html_data .= '<li><strong>Author:</strong> ' . $book_details->preference_author . '</li>';
    $html_data .= '<li><strong>Title:</strong> ' . $book_details->preference_book . '</li>';
    $html_data .= '<li><strong>Publisher:</strong> ' . $book_details->preference_publisher . '</li>';
    $html_data .= '<li><strong>Year:</strong> ' . $book_details->preference_year . '</li>';
    $html_data .= '<li><strong>Edition:</strong> ' . $book_details->preference_edition . '</li>';
    $html_data .= '</ul></td><td style="width:50%;vertical-align:top;">';
    $html_data .= '<strong>About the Contributor</strong><ul>';
    $html_data .= '<li><strong>Name:</strong> ' . $book_details->proposal_full_name . '</li>';
    $html_data .= '<li><strong>Faculty:</strong> ' . $book_details->proposal_faculty . '</li>';
    $html_data .= '<li><strong>Reviewer:</strong> ' . $book_details->proposal_reviewer . '</li>';
    $html_data .= '<li><strong>Course:</strong> ' . $book_details->proposal_course . ', ' . $book_details->proposal_branch . ', ' . $book_details->proposal_university . '</li>';
    $html_data .= '</ul></td></tr></table>';

    return $html_data;
  }

  public function _list_of_chapters($preference_id = 0) {
    $book_chapters = [0 => $this->t('Please select...')];
    if (!$preference_id) return $book_chapters;

    $connection = \Drupal::database();
    $query = $connection->select('textbook_companion_chapter', 'tcc');
    $query->fields('tcc', ['id', 'name', 'number']);
    $query->condition('preference_id', $preference_id);
    $query->orderBy('number', 'ASC');
    $results = $query->execute()->fetchAll();

    foreach ($results as $chapter) {
      $book_chapters[$chapter->id] = $chapter->number . '. ' . $chapter->name;
    }

    return $book_chapters;
  }

  public function _list_of_examples($chapter_id = 0, $selected_example = 0) {
  $examples = [0 => $this->t('Please select...')];
  if (!$chapter_id) {
    return $examples;
  }

  $connection = \Drupal::database();
  $query = $connection->select('textbook_companion_example', 'tce');
  $query->fields('tce', ['id', 'number', 'caption']);
  $query->condition('chapter_id', $chapter_id);
  $query->condition('approval_status', 1);
  $results = $query->execute()->fetchAll();

  foreach ($results as $example) {
    $examples[$example->id] = $example->number . '. ' . $example->caption;
  }

  // If the selected example is not in the list, add it manually
  // if ($selected_example && !isset($examples[$selected_example])) {
  //   $examples[$selected_example] = $selected_example . ' (Selected)';
  // }

  return $examples;
}                 

  
    public function submitForm(array &$form, FormStateInterface $form_state) {
    }
}