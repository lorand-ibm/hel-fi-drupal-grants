<?php

namespace Drupal\grants_handler\Plugin\WebformHandler;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\grants_attachments\AttachmentRemover;
use Drupal\grants_attachments\AttachmentUploader;
use Drupal\grants_attachments\Plugin\WebformElement\GrantsAttachments;
use Drupal\grants_metadata\AtvSchema;
use Drupal\grants_metadata\TypedData\Definition\YleisavustusHakemusDefinition;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvFailedToConnectException;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform example handler.
 *
 * @WebformHandler(
 *   id = "grants_handler",
 *   label = @Translation("Grants Handler"),
 *   category = @Translation("helfi"),
 *   description = @Translation("Grants webform handler"),
 *   cardinality =
 *   \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission =
 *   \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class GrantsHandler extends WebformHandlerBase {

  /**
   * Form data saved because the data in saved submission is not preserved.
   *
   * @var array
   *   Holds submitted data for processing in confirmForm.
   *
   * When we want to delete all submitted data before saving
   * submission to database. This way we can still use webform functionality
   * while not saving any sensitive data to local drupal.
   */
  private array $submittedFormData = [];

  /**
   * Field names for attachments.
   *
   * @var string[]
   *
   * @todo get field names from form where field type is attachment.
   */
  protected static array $attachmentFieldNames = [
    'vahvistettu_tilinpaatos' => 43,
    'vahvistettu_toimintakertomus' => 4,
    'vahvistettu_tilin_tai_toiminnantarkastuskertomus' => 5,
    'vuosikokouksen_poytakirja' => 8,
    'toimintasuunnitelma' => 1,
    'talousarvio' => 2,
    'muu_liite' => 0,
  ];

  /**
   * Holds application statuses in.
   *
   * @var string[]
   */
  public static array $applicationStatuses = [
    'DRAFT' => 'DRAFT',
    'SUBMITTED' => 'SUBMITTED',
    'SENT' => 'SENT',
    'RECEIVED' => 'RECEIVED',
    'PENDING' => 'PENDING',
    'PROCESSING' => 'PROCESSING',
    'READY' => 'READY',
    'DONE' => 'DONE',
    'REJECTED' => 'REJECTED',
  ];

  /**
   * Array containing added file ids for removal & upload.
   *
   * @var array
   */
  private array $attachmentFileIds;

  /**
   * Uploader service.
   *
   * @var \Drupal\grants_attachments\AttachmentUploader
   */
  protected AttachmentUploader $attachmentUploader;

  /**
   * Remover service.
   *
   * @var \Drupal\grants_attachments\AttachmentRemover
   */
  protected AttachmentRemover $attachmentRemover;

  /**
   * Application type.
   *
   * @var string
   */
  protected string $applicationType;

  /**
   * Application type ID.
   *
   * @var string
   */
  protected string $applicationTypeID;

  /**
   * Generated application number.
   *
   * @var string
   */
  protected string $applicationNumber;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * User data from helsinkiprofiili & auth methods.
   *
   * @var \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData
   */
  protected HelsinkiProfiiliUserData $userExternalData;

  /**
   * Access ATV backend.
   *
   * @var \Drupal\grants_metadata\AtvSchema
   */
  protected AtvSchema $atvSchema;

  /**
   * Access GRants profile.
   *
   * @var \Drupal\grants_profile\GrantsProfileService
   */
  protected GrantsProfileService $grantsProfileService;

  /**
   * Access ATV backend.
   *
   * @var \Drupal\helfi_atv\AtvService
   */
  protected AtvService $atvService;

  /**
   * Date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected DateFormatter $dateFormatter;

  /**
   * Holds document fetched from ATV for checks.
   *
   * @var \Drupal\helfi_atv\AtvDocument
   */
  protected AtvDocument $atvDocument;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    /** @var \Drupal\Core\DependencyInjection\Container $container */
    $instance->attachmentUploader = $container->get('grants_attachments.attachment_uploader');
    $instance->attachmentRemover = $container->get('grants_attachments.attachment_remover');

    // Make sure we have empty array as initial value.
    $instance->attachmentFileIds = [];

    $instance->currentUser = $container->get('current_user');

    $instance->userExternalData = $container->get('helfi_helsinki_profiili.userdata');

    /** @var \Drupal\helfi_atv\AtvService atvService */
    $instance->atvService = $container->get('helfi_atv.atv_service');

    /** @var \Drupal\grants_metadata\AtvSchema atvSchema */
    $instance->atvSchema = $container->get('grants_metadata.atv_schema');
    $instance->atvSchema->setSchema(getenv('ATV_SCHEMA_PATH'));

    /** @var \Drupal\grants_profile\GrantsProfileService $grantsProfileService */
    $instance->grantsProfileService = \Drupal::service('grants_profile.service');

    /** @var \Drupal\grants_profile\GrantsProfileService $grantsProfileService */
    $instance->dateFormatter = \Drupal::service('date.formatter');

    return $instance;
  }

  /**
   * Atv document holding this application.
   *
   * @param string $transactionId
   *   Id of the transaction.
   *
   * @return \Drupal\helfi_atv\AtvDocument
   *   FEtched document.
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function getAtvDocument(string $transactionId): AtvDocument {

    if (!isset($this->atvDocument)) {
      $res = $this->atvService->searchDocuments([
        'transaction_id' => $transactionId,
      ]);
      $this->atvDocument = reset($res);
    }

    return $this->atvDocument;
  }

  /**
   * Get file fields.
   *
   * @return string[]
   *   Attachment fields.
   */
  public static function getAttachmentFieldNames($preventKeys = FALSE): array {
    if ($preventKeys) {
      return self::$attachmentFieldNames;
    }
    return array_keys(self::$attachmentFieldNames);
  }

  /**
   * Return Application environment shortcode.
   *
   * @return string
   *   Shortcode from current environment.
   */
  public static function getAppEnv(): string {
    $appEnv = getenv('APP_ENV');

    if ($appEnv == 'development') {
      $appParam = 'DEV';
    }
    else {
      if ($appEnv == 'production') {
        $appParam = 'PROD';
      }
      else {
        if ($appEnv == 'testing') {
          $appParam = 'TEST';
        }
        else {
          if ($appEnv == 'staging') {
            $appParam = 'STAGE';
          }
          else {
            $appParam = 'LOCAL';
          }
        }
      }
    }
    return $appParam;
  }

  /**
   * Convert EUR format value to "double" .
   *
   * @param string $value
   *   Value to be converted.
   *
   * @return float
   *   Floated value.
   */
  private function grantsHandlerConvertToFloat(string $value): float {
    $value = str_replace(['€', ',', ' '], ['', '.', ''], $value);
    $value = (float) $value;
    return $value;
  }

  /**
   * Convert EUR format value to "double" .
   *
   * @param string|null $value
   *   Value to be converted.
   *
   * @return float
   *   Floated value.
   */
  public static function convertToFloat(?string $value = ''): float {
    $value = str_replace(['€', ',', ' '], ['', '.', ''], $value);
    $value = (float) $value;
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'debug' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    // Development.
    $form['development'] = [
      '#type' => 'details',
      '#title' => $this->t('Development settings'),
    ];
    $form['development']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, every handler method invoked will be displayed onscreen to all users.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['debug'],
    ];

    return $this->setSettingsParents($form);
  }

  /**
   * Calculate & set total values from added elements in webform.
   */
  protected function setTotals() {

    if (isset($this->submittedFormData['myonnetty_avustus']) &&
      is_array($this->submittedFormData['myonnetty_avustus'])) {
      $tempTotal = 0;
      foreach ($this->submittedFormData['myonnetty_avustus'] as $key => $item) {
        $amount = $this->grantsHandlerConvertToFloat($item['amount']);
        $tempTotal += $amount;
      }
      $this->submittedFormData['myonnetty_avustus_total'] = $tempTotal;
    }

    if (isset($this->submittedFormData['haettu_avustus_tieto']) &&
      is_array($this->submittedFormData['haettu_avustus_tieto'])) {
      $tempTotal = 0;
      foreach ($this->submittedFormData['haettu_avustus_tieto'] as $item) {
        $amount = $this->grantsHandlerConvertToFloat($item['amount']);
        $tempTotal += $amount;
      }
      $this->submittedFormData['haettu_avustus_tieto_total'] = $tempTotal;

    }

    // @todo properly get amount
    $this->submittedFormData['compensation_total_amount'] = $tempTotal;

  }

  /**
   * Generate application number from submission id.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $submission
   *   Webform data.
   *
   * @return string
   *   Generated number.
   */
  public static function createApplicationNumber(WebformSubmission $submission): string {

    $appParam = self::getAppEnv();

    return 'GRANTS-' . $appParam . '-' . sprintf('%08d', $submission->serial());
  }

  /**
   * Generate application number from submission id.
   *
   * @param string $applicationNumber
   *   String to try and parse submission id from. Ie GRANTS-DEV-00000098.
   *
   * @return \Drupal\webform\Entity\WebformSubmission|null
   *   Webform submission.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function submissionObjectFromApplicationNumber(string $applicationNumber): ?WebformSubmission {

    $exploded = explode('-', $applicationNumber);
    $number = end($exploded);
    $submissionSerial = ltrim($number, '0');

    $result = \Drupal::entityTypeManager()
      ->getStorage('webform_submission')
      ->loadByProperties([
        'serial' => $submissionSerial,
      ]);

    // If there's no local submission with given serial
    // we can actually create that object on the fly and use that for editing.
    if (empty($result)) {
      try {
        // Create submission.
        // @todo remove hardcoded form type at some point.
        $createdSubmissionObject = WebformSubmission::create([
          'webform_id' => 'yleisavustushakemus',
          'serial' => $submissionSerial,
        ]);
        // Make sure serial is set.
        $createdSubmissionObject->set('serial', $submissionSerial);

        /** @var \Drupal\helfi_atv\AtvService $atvService */
        $atvService = \Drupal::service('helfi_atv.atv_service');

        /** @var \Drupal\grants_metadata\AtvSchema $atvSchema */
        $atvSchema = \Drupal::service('grants_metadata.atv_schema');

        // Get document from ATV.
        $document = $atvService->searchDocuments([
          'transaction_id' => $applicationNumber,
        ],
        TRUE);

        /** @var \Drupal\helfi_atv\AtvDocument $document */
        $document = reset($document);

        // Save submission BEFORE setting data so we don't accidentally
        // save anything.
        $createdSubmissionObject->save();

        // Set submission data from parsed mapper.
        $createdSubmissionObject->setData($atvSchema->documentContentToTypedData(
          $document->getContent(),
          YleisavustusHakemusDefinition::create('grants_metadata_yleisavustushakemus')));

        return $createdSubmissionObject;

      }
      catch (
      AtvDocumentNotFoundException |
      AtvFailedToConnectException |
      GuzzleException |
      TempStoreException |
      EntityStorageException $e) {
        return NULL;
      }
    }

    return reset($result);
  }

  /**
   * Set up sender details from helsinkiprofiili data.
   */
  private function parseSenderDetails() {
    // Set sender information after save so no accidental saving of data.
    // @todo Think about how sender info should be parsed, maybe in own.
    $userProfileData = $this->userExternalData->getUserProfileData();
    $userData = $this->userExternalData->getUserData();

    if (isset($userProfileData["myProfile"])) {
      $data = $userProfileData["myProfile"];
    }
    else {
      $data = $userProfileData;
    }

    // If no userprofile data, we need to hardcode these values.
    // @todo Remove hardcoded values when tunnistamo works.
    if ($userProfileData == NULL || $userData == NULL) {
      $this->submittedFormData['sender_firstname'] = 'NoTunnistamo';
      $this->submittedFormData['sender_lastname'] = 'NoTunnistamo';
      $this->submittedFormData['sender_person_id'] = 'NoTunnistamo';
      $this->submittedFormData['sender_user_id'] = '280f75c5-6a20-4091-b22d-dfcdce7fef60';
      $this->submittedFormData['sender_email'] = 'NoTunnistamo';

    }
    else {
      $userData = $this->userExternalData->getUserData();
      $this->submittedFormData['sender_firstname'] = $data["verifiedPersonalInformation"]["firstName"];
      $this->submittedFormData['sender_lastname'] = $data["verifiedPersonalInformation"]["lastName"];
      $this->submittedFormData['sender_person_id'] = $data["verifiedPersonalInformation"]["nationalIdentificationNumber"];
      $this->submittedFormData['sender_user_id'] = $userData["sub"];
      $this->submittedFormData['sender_email'] = $data["primaryEmail"]["email"];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(
    array &$form,
    FormStateInterface $form_state,
    WebformSubmissionInterface $webform_submission
  ) {

    parent::validateForm($form, $form_state, $webform_submission);

    // Get current page.
    $currentPage = $form["progress"]["#current_page"];

    // 1_hakijan_tiedot
    // 2_avustustiedot
    // 3_yhteison_tiedot
    // lisatiedot_ja_liitteet
    // webform_preview
    $this->submittedFormData = $webform_submission->getData();

    foreach ($this->submittedFormData["myonnetty_avustus"] as $key => $value) {
      $this->submittedFormData["myonnetty_avustus"][$key]['issuerName'] = $value['issuer_name'];
      unset($this->submittedFormData["myonnetty_avustus"][$key]['issuer_name']);
    }
    foreach ($this->submittedFormData["haettu_avustus_tieto"] as $key => $value) {
      $this->submittedFormData["haettu_avustus_tieto"][$key]['issuerName'] = $value['issuer_name'];
      unset($this->submittedFormData["haettu_avustus_tieto"][$key]['issuer_name']);
    }

    // Only validate set forms.
    if ($currentPage === 'lisatiedot_ja_liitteet' || $currentPage === 'webform_preview') {
      // Loop through fieldnames and validate fields.
      foreach (self::getAttachmentFieldNames() as $fieldName) {
        $fValues = $form_state->getValue($fieldName);
        $this->validateAttachmentField(
          $fieldName,
          $form_state,
          $form["elements"]["lisatiedot_ja_liitteet"]["liitteet"][$fieldName]["#title"]
        );
      }
    }

    $errors = $form_state->getErrors();
    if (!empty($errors)) {
      $this->messenger()
        ->addWarning($this->t('Errors in form data, please fix them before going on.'));
    }
  }

  /**
   * Validate single attachment field.
   *
   * @param string $fieldName
   *   Name of the field in validation.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   * @param string $fieldTitle
   *   Field title for errors.
   *
   * @todo think about how attachment validation logic could be moved to the
   *   component.
   */
  private function validateAttachmentField(string $fieldName, FormStateInterface $form_state, string $fieldTitle) {
    // Get value.
    $values = $form_state->getValue($fieldName);

    $args = [];
    if (isset($values[0]) && is_array($values[0])) {
      $args = $values;
    }
    else {
      $args[] = $values;
    }

    foreach ($args as $value) {
      // Muu liite is optional.
      if ($fieldName !== 'muu_liite' && ($value === NULL || empty($value))) {
        $form_state->setErrorByName($fieldName, $this->t('@fieldname field is required', [
          '@fieldname' => $fieldTitle,
        ]));
      }
      if ($value !== NULL) {
        // If attachment is uploaded, make sure no other field is selected.
        if (isset($value['attachment']) && is_int($value['attachment'])) {
          if ($value['isDeliveredLater'] === "1") {
            $form_state->setErrorByName("[" . $fieldName . "][isDeliveredLater]", $this->t('@fieldname has file added, it cannot be added later.', [
              '@fieldname' => $fieldTitle,
            ]));
          }
          if ($value['isIncludedInOtherFile'] === "1") {
            $form_state->setErrorByName("[" . $fieldName . "][isIncludedInOtherFile]", $this->t('@fieldname has file added, it cannot belong to other file.', [
              '@fieldname' => $fieldTitle,
            ]));
          }
        }
        else {
          if ((!empty($value) && !isset($value['attachment']) && ($value['attachment'] === NULL && $value['attachmentName'] === ''))) {
            if (empty($value['isDeliveredLater']) && empty($value['isIncludedInOtherFile'])) {
              $form_state->setErrorByName("[" . $fieldName . "][isDeliveredLater]", $this->t('@fieldname has no file uploaded, it must be either delivered later or be included in other file.', [
                '@fieldname' => $fieldTitle,
              ]));
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webform_submission) {

    if (empty($this->submittedFormData)) {
      $this->submittedFormData = $webform_submission->getData();
    }

    if (!empty($this->submittedFormData)) {
      $this->setTotals();
      $this->parseSenderDetails();
      // Set submission data to empty.
      // form will still contain submission details, IP time etc etc.
      $webform_submission->setData([]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $this->applicationType = $webform_submission->getWebform()
      ->getThirdPartySetting('grants_metadata', 'applicationType');
    $this->applicationTypeID = $webform_submission->getWebform()
      ->getThirdPartySetting('grants_metadata', 'applicationTypeID');

    $dt = new \DateTime();
    $dt->setTimestamp($webform_submission->getCreatedTime());
    $dt->setTimezone(new \DateTimeZone('UTC'));
    $this->submittedFormData['form_timestamp'] = $dt->format('Y-m-d\TH:i:s\.\0\0\0\Z');

    // @todo check community_practices_business value and where to get it from.
    $this->submittedFormData['community_practices_business'] = FALSE;

    if (isset($this->submittedFormData["finalize_application"]) &&
      $this->submittedFormData["finalize_application"] == 1) {
      $this->submittedFormData['status'] = 'SUBMITTED';
    }

    if (!isset($this->submittedFormData['application_number'])) {
      $this->applicationNumber = self::createApplicationNumber($webform_submission);
      $this->submittedFormData['application_type_id'] = $this->applicationTypeID;
      $this->submittedFormData['application_type'] = $this->applicationType;
      $this->submittedFormData['application_number'] = $this->applicationNumber;
      // Apparently you CANNOT have this set in new applications,
      // integration seems to fail.
      $this->submittedFormData['form_update'] = FALSE;
    }
    else {
      $this->applicationNumber = $this->submittedFormData['application_number'];
      if ($this->submittedFormData['status'] === 'SUBMITTED') {
        $this->submittedFormData['form_update'] = FALSE;
      }
      else {
        $this->submittedFormData['form_update'] = TRUE;
      }

    }

  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(
    array &$form,
    FormStateInterface $form_state,
    WebformSubmissionInterface $webform_submission) {

    $dataDefinition = YleisavustusHakemusDefinition::create('grants_metadata_yleisavustushakemus');

    $typeManager = $dataDefinition->getTypedDataManager();
    $applicationData = $typeManager->create($dataDefinition);

    $this->submittedFormData['attachments'] = $this->parseAttachments($form);

    try {
      $applicationData->setValue($this->submittedFormData);
    }
    catch (\Exception $e) {
    }

    $violations = $applicationData->validate();

    if ($violations->count() > 0) {
      foreach ($violations as $violation) {
        $this->getLogger('grants_handler')
          ->debug($this->t('Error with data. Property: %property. Message: %message', [
            '%property' => $violation->getPropertyPath(),
            '%message' => $violation->getMessage(),
          ]));
        $this->messenger()
          ->addError($this->t('Data not saved, error with data. (This functionality WILL change before production.) Property: %property. Message: %message', [
            '%property' => $violation->getPropertyPath(),
            '%message' => $violation->getMessage(),
          ]));
      }
      return;
    }

    // If there's violations in data.
    if ($violations->count() == 0) {

      $appDocument = $this->atvSchema->typedDataToDocumentContent($applicationData);

      $endpoint = getenv('AVUSTUS2_ENDPOINT');
      $username = getenv('AVUSTUS2_USERNAME');
      $password = getenv('AVUSTUS2_PASSWORD');

      if (!empty($this->configuration['debug'])) {
        $t_args = [
          '@endpoint' => $endpoint,
        ];
        $this->messenger()
          ->addMessage($this->t('DEBUG: Endpoint:: @endpoint', $t_args));
      }

      $myJSON = Json::encode($appDocument);

      // If debug, print out json.
      if ($this->isDebug()) {
        $t_args = [
          '@myJSON' => $myJSON,
        ];
        $this->getLogger('grants_handler')
          ->debug('DEBUG: Sent JSON: @myJSON', $t_args);
      }
      // If backend mode is dev, then don't post things to backend.
      if (getenv('BACKEND_MODE') === 'dev') {
        $this->messenger()
          ->addWarning($this->t('Backend DEV mode on, no posting to backend is done.'));
      }
      else {
        try {
          $client = \Drupal::httpClient();
          $res = $client->post($endpoint, [
            'auth' => [$username, $password, "Basic"],
            'body' => $myJSON,
          ]);

          $status = $res->getStatusCode();

          if ($status === 201) {
            $this->attachmentUploader->setDebug($this->isDebug());
            $attachmentResult = $this->attachmentUploader->uploadAttachments(
              $this->attachmentFileIds,
              $this->applicationNumber,
              $this->isDebug()
            );

            foreach ($attachmentResult as $attResult) {
              if ($attResult['upload'] === TRUE) {
                $this->messenger()
                  ->addStatus(
                    $this->t(
                      'Attachment (@filename) uploaded',
                      [
                        '@filename' => $attResult['filename'],
                      ]));
              }
              else {
                $this->messenger()
                  ->addStatus(
                    $this->t(
                      'Attachment (@filename) upload failed with message: @msg. Event has been logged.',
                      [
                        '@filename' => $attResult['filename'],
                        '@msg' => $attResult['msg'],
                      ]));
              }
            }

            $url = Url::fromRoute(
              'grants_profile.view_application',
              ['document_uuid' => $this->applicationNumber],
              [
                'attributes' => [
                  'data-drupal-selector' => 'application-saved-successfully-link',
                ],
              ]
            );

            // TÄHÄN TSEKKAA RESULTTI.
            // @todo print message for every attachment
            $this->messenger()
              ->addStatus(
                $this->t(
                  'Grant application (@number), 
                  see application status from @link',
                  [
                    '@number' => $this->applicationNumber,
                    '@link' => Link::fromTextAndUrl('here', $url)->toString(),
                  ]));

            $this->attachmentRemover->removeGrantAttachments(
              $this->attachmentFileIds,
              $attachmentResult,
              $this->applicationNumber,
              $this->isDebug(),
              $webform_submission->id()
            );
          }
        }
        catch (\Exception $e) {
          $this->messenger()->addError($e->getMessage());
          $this->getLogger('grants_handler')->error($e->getMessage());
        }

      }
    }
  }

  /**
   * Helper to find out if we're debugging or not.
   *
   * @return bool
   *   If debug mode is on or not.
   */
  protected function isDebug(): bool {
    return !empty($this->configuration['debug']);
  }

  /**
   * Display the invoked plugin method to end user.
   *
   * @param string $method_name
   *   The invoked method name.
   * @param string $context1
   *   Additional parameter passed to the invoked method name.
   */
  protected function debug($method_name, $context1 = NULL) {
    if (!empty($this->configuration['debug'])) {
      $t_args = [
        '@id' => $this->getHandlerId(),
        '@class_name' => get_class($this),
        '@method_name' => $method_name,
        '@context1' => $context1,
      ];
      $this->messenger()
        ->addWarning($this->t('Invoked @id: @class_name:@method_name @context1', $t_args), TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['debug'] = (bool) $form_state->getValue('debug');
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessConfirmation(array &$variables) {
    $this->debug(__FUNCTION__);
  }

  /**
   * Parse attachments from POST.
   *
   * @return array[]
   *   Parsed attchments.
   */
  private function parseAttachments($form): array {

    // $thisDocument = $this->getAtvDocument($this->applicationNumber);
    $attachmentsArray = [];
    $attachmentHeaders = GrantsAttachments::$fileTypes;
    $filenames = [];
    foreach (self::getAttachmentFieldNames() as $attachmentFieldName) {
      $field = $this->submittedFormData[$attachmentFieldName];
      $descriptionKey = self::$attachmentFieldNames[$attachmentFieldName];

      // $descriptionValue = $form["elements"]["lisatiedot_ja_liitteet"]["liitteet"][$attachmentFieldName]["#title"];
      $descriptionValue = $attachmentHeaders[$descriptionKey];

      $fileType = NULL;

      // Since we have to support multiple field elements, we need to
      // handle all as they were a multifield.
      $args = [];
      if (isset($field[0]) && is_array($field[0])) {
        $args = $field;
      }
      else {
        $args[] = $field;
      }

      // Loop args & create attachement field.
      foreach ($args as $fieldElement) {
        if (is_array($fieldElement)) {

          if (isset($fieldElement["fileType"]) && $fieldElement["fileType"] !== "") {
            $fileType = $fieldElement["fileType"];
          }
          else {
            if (isset($form["elements"]["lisatiedot_ja_liitteet"]["liitteet"][$attachmentFieldName]["#filetype"])) {
              $fileType = $form["elements"]["lisatiedot_ja_liitteet"]["liitteet"][$attachmentFieldName]["#filetype"];
            }
            else {
              $fileType = '0';
            }
          }

          $parsedArray = $this->getAttachmentByFieldValue(
            $fieldElement, $descriptionValue, $fileType);

          if (!empty($parsedArray)) {
            if (!isset($parsedArray['fileName']) || !in_array($parsedArray['fileName'], $filenames)) {
              $attachmentsArray[] = $parsedArray;
              if (isset($parsedArray['fileName'])) {
                $filenames[] = $parsedArray['fileName'];
              }
            }
          }
        }
      }
    }

    if (isset($this->submittedFormData["account_number"])) {
      $selectedAccountNumber = $this->submittedFormData["account_number"];
      $selectedCompany = $this->grantsProfileService->getSelectedCompany();
      $grantsProfileDocument = $this->grantsProfileService->getGrantsProfile($selectedCompany);
      $profileContent = $grantsProfileDocument->getContent();

      $applicationDocument = FALSE;
      try {
        $applicationDocumentResults = $this->atvService->searchDocuments([
          'transaction_id' => $this->applicationNumber,
        ]);
        $applicationDocument = reset($applicationDocumentResults);
      }
      catch (AtvDocumentNotFoundException | AtvFailedToConnectException | GuzzleException $e) {
      }

      $accountConfirmationExists = FALSE;
      if ($applicationDocument) {
        $filename = md5($selectedAccountNumber);

        $aa = $applicationDocument->getAttachments();

        foreach ($aa as $attachment) {
          if (str_contains($attachment['filename'], $filename)) {
            $accountConfirmationExists = TRUE;
            break;
          }
          $found = array_filter($filenames, function ($fn) use ($filename) {
            return str_contains($fn, $filename);
          });
          if (!empty($found)) {
            $accountConfirmationExists = TRUE;
            break;
          }
        }

        if (!$accountConfirmationExists) {
          $found = array_filter($attachmentsArray, function ($fn) use ($filename) {
            if (!isset($fn['fileName'])) {
              return FALSE;
            }
            return str_contains($fn['fileName'], $filename);
          });
          if (!empty($found)) {
            $accountConfirmationExists = TRUE;
          }
        }

      }

      if (!$accountConfirmationExists) {
        $selectedAccount = NULL;
        foreach ($profileContent['bankAccounts'] as $account) {
          if ($account['bankAccount'] == $selectedAccountNumber) {
            $selectedAccount = $account;
          }
        }
        $selectedAccountConfirmation = FALSE;
        if ($selectedAccount['confirmationFile']) {
          $selectedAccountConfirmation = $grantsProfileDocument->getAttachmentForFilename($selectedAccount['confirmationFile']);
        }

        if ($selectedAccountConfirmation) {
          try {
            // Get file.
            $file = $this->atvService->getAttachment($selectedAccountConfirmation['href']);
            // Add file to attachments for uploading.
            $this->attachmentFileIds[] = $file->id();
          }
          catch (AtvDocumentNotFoundException | AtvFailedToConnectException | GuzzleException $e) {
            $this->loggerFactory->get('grants_handler')
              ->error($e->getMessage());
            $this->messenger()
              ->addError('Bank account confirmation file attachment failed.');
          }

          $attachmentsArray[] = [
            'description' => 'Confirmation for account ' . $selectedAccount["bankAccount"],
            'fileName' => $selectedAccount["confirmationFile"],
            'isNewAttachment' => TRUE,
            'fileType' => 101,
            'isDeliveredLater' => FALSE,
            'isIncludedInOtherFile' => FALSE,
            // @todo a better way to strip host from atv url.
          ];
        }
      }
    }

    return $attachmentsArray;
  }

  /**
   * Extract attachments from form data.
   *
   * @param array $field
   *   The field parsed.
   * @param string $fieldDescription
   *   The field description from form element title.
   * @param string $fileType
   *   Filetype id from element configuration.
   *
   * @return \stdClass[]
   *   Data for JSON.
   */
  private function getAttachmentByFieldValue(array $field, string $fieldDescription, string $fileType): array {

    $retval = [
      'description' => (isset($field['description']) && $field['description'] !== "") ? $field['description'] : $fieldDescription,
    ];
    $retval['fileType'] = (int) $fileType;
    // We have uploaded file. THIS time. Not previously.
    if (isset($field['attachment']) && $field['attachment'] !== NULL && !empty($field['attachment'])) {

      $file = File::load($field['attachment']);
      if ($file) {
        // Add file id for easier usage in future.
        $this->attachmentFileIds[] = $field['attachment'];

        $retval['fileName'] = $file->getFilename();
        $retval['isNewAttachment'] = TRUE;
        $retval['isDeliveredLater'] = FALSE;
        $retval['isIncludedInOtherFile'] = FALSE;
      }
    }
    else {
      // If other filetype and no attachment already set, we don't add them to
      // retval since we don't want to fill attachments with empty other files.
      if (($fileType === "0" || $fileType === '101') && empty($field["attachmentName"])) {
        return [];
      }
      // No upload, process accordingly.
      if ($field['fileStatus'] == 'new' || empty($field['fileStatus'])) {
        if (isset($field['isDeliveredLater'])) {
          $retval['isDeliveredLater'] = $field['isDeliveredLater'] === "1";
        }
        if (isset($field['isIncludedInOtherFile'])) {
          $retval['isIncludedInOtherFile'] = $field['isIncludedInOtherFile'] === "1";
        }
      }
      if ($field['fileStatus'] === 'uploaded') {
        if (isset($field['attachmentName'])) {
          $retval['fileName'] = $field["attachmentName"];
        }
        $retval['isDeliveredLater'] = FALSE;
        $retval['isIncludedInOtherFile'] = FALSE;
        $retval['isNewAttachment'] = FALSE;
      }
      if ($field['fileStatus'] == 'deliveredLater') {
        if ($field['attachmentName']) {
          $retval['fileName'] = $field["attachmentName"];
        }
        if (isset($field['isDeliveredLater'])) {
          $retval['isDeliveredLater'] = $field['isDeliveredLater'] === "1";
        }
        else {
          $retval['isDeliveredLater'] = '0';
        }

        if (isset($field['isIncludedInOtherFile'])) {
          $retval['isIncludedInOtherFile'] = $field['isIncludedInOtherFile'] === "1";
        }
        else {
          $retval['isIncludedInOtherFile'] = '0';
        }
      }
    }
    return $retval;
  }

}
