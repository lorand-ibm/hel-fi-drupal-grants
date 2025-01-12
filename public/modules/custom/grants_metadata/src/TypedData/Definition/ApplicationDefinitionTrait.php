<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Base class for data typing & mapping.
 */
trait ApplicationDefinitionTrait {

  /**
   * Base data definitions for all.
   */
  public function getBaseProperties(): array {
    $info['applicant_type'] = DataDefinition::create('integer')
      // // ->setRequired(TRUE)
      ->setLabel('Hakijan tyyppi')
      ->setSetting('jsonPath', [
        'compensation',
        'applicantInfoArray',
        'applicantType',
      ])
      ->addConstraint('NotBlank');

    $info['company_number'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('Rekisterinumero')
      ->setSetting('jsonPath', [
        'compensation',
        'applicantInfoArray',
        'companyNumber',
      ])
      ->addConstraint('NotBlank');

    $info['community_official_name'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('Yhteisön nimi')
      ->setSetting('jsonPath', [
        'compensation',
        'applicantInfoArray',
        'communityOfficialName',
      ])
      ->addConstraint('NotBlank');

    $info['community_official_name_short'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('Yhteisön lyhenne')
      ->setSetting('jsonPath', [
        'compensation',
        'applicantInfoArray',
        'communityOfficialNameShort',
      ])
      ->addConstraint('NotBlank');

    $info['registration_date'] = DataDefinition::create('datetime_iso8601')
      // ->setRequired(TRUE)
      ->setLabel('Rekisteröimispäivä')
      ->setSetting('jsonPath', [
        'compensation',
        'applicantInfoArray',
        'registrationDate',
      ])
      ->addConstraint('NotBlank');

    $info['founding_year'] = DataDefinition::create('integer')
      // ->setRequired(TRUE)
      ->setLabel('Perustamisvuosi')
      ->setSetting('jsonPath', [
        'compensation',
        'applicantInfoArray',
        'foundingYear',
      ])
      ->addConstraint('NotBlank');

    $info['home'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('Kotipaikka')
      ->setSetting('jsonPath', ['compensation', 'applicantInfoArray', 'home'])
      ->addConstraint('NotBlank');

    $info['homepage'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('www-sivut')
      ->setSetting('jsonPath', [
        'compensation',
        'applicantInfoArray',
        'homePage',
      ])
      ->setSetting('defaultValue', "");

    $info['email'] = DataDefinition::create('email')
      // ->setRequired(TRUE)
      ->setLabel('Sähköpostiosoite')
      ->setSetting('jsonPath', [
        'compensation',
        'applicantInfoArray',
        'email',
      ])
      ->setSetting('typeOverride', [
        'dataType' => 'email',
        'jsonType' => 'string',
      ])
      ->addConstraint('NotBlank')
      ->addConstraint('Email');

    $info['applicant_officials'] = ListDataDefinition::create('grants_profile_application_official')
      // ->setRequired(TRUE)
      ->setSetting('jsonPath', ['compensation', 'applicantOfficialsArray'])
      ->setSetting('defaultValue', [])
      ->setLabel('applicantOfficialsArray');

    $info['contact_person'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('currentAddressInfoArray=>contactPerson')
      ->setSetting('jsonPath', [
        'compensation',
        'currentAddressInfoArray',
        'contactPerson',
      ])
      ->addConstraint('NotBlank');

    $info['contact_person_phone_number'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('currentAddressInfoArray=>phoneNumber')
      ->setSetting('jsonPath', [
        'compensation',
        'currentAddressInfoArray',
        'phoneNumber',
      ])
      ->addConstraint('NotBlank');

    $info['contact_person_street'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('currentAddressInfoArray=>street')
      ->setSetting('jsonPath', [
        'compensation',
        'currentAddressInfoArray',
        'street',
      ])
      ->addConstraint('NotBlank');

    $info['contact_person_city'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('currentAddressInfoArray=>city')
      ->setSetting('jsonPath', [
        'compensation',
        'currentAddressInfoArray',
        'city',
      ])
      ->addConstraint('NotBlank');

    $info['contact_person_post_code'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('currentAddressInfoArray=>postCode')
      ->setSetting('jsonPath', [
        'compensation',
        'currentAddressInfoArray',
        'postCode',
      ])
      ->addConstraint('NotBlank');

    $info['contact_person_country'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('currentAddressInfoArray=>country')
      ->setSetting('jsonPath', [
        'compensation',
        'currentAddressInfoArray',
        'country',
      ])
      ->addConstraint('NotBlank');

    $info['application_type'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('Application type')
      ->setSetting('jsonPath', [
        'compensation',
        'applicationInfoArray',
        'applicationType',
      ]);
    // ->addConstraint('NotBlank')
    $info['application_type_id'] = DataDefinition::create('string')
      ->setLabel('Application type id')
      ->setSetting('jsonPath', [
        'compensation',
        'applicationInfoArray',
        'applicationTypeID',
      ]);
    // ->setRequired(TRUE)
    // ->addConstraint('NotBlank')
    // ->addConstraint('NotEmptyValue')
    $info['form_timestamp'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('formTimeStamp')
      ->setSetting('jsonPath', [
        'compensation',
        'applicationInfoArray',
        'formTimeStamp',
      ]);
    // ->addConstraint('NotBlank')
    $info['application_number'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('applicationNumber')
      ->setSetting('jsonPath', [
        'compensation',
        'applicationInfoArray',
        'applicationNumber',
      ]);
    // ->addConstraint('NotBlank')
    $info['status'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('Status')
      ->setSetting('jsonPath', [
        'compensation',
        'applicationInfoArray',
        'status',
      ]);
    // ->addConstraint('NotBlank')
    $info['acting_year'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('Acting year')
      ->setSetting('defaultValue', "")
      ->setSetting('jsonPath', [
        'compensation',
        'applicationInfoArray',
        'actingYear',
        // ->addConstraint('NotBlank')
      ]);

    $info['account_number'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('accountNumber')
      ->setSetting('jsonPath', [
        'compensation',
        'bankAccountArray',
        'accountNumber',
      ])
      ->addConstraint('NotBlank');

    $info['compensation_purpose'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('')
      ->setSetting('jsonPath', [
        'compensation',
        'compensationInfo',
        'generalInfoArray',
        'purpose',
      ])
      ->addConstraint('NotBlank');

    $info['compensation_boolean'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('compensationPreviousYear')
      ->setSetting('defaultValue', FALSE)
      ->setSetting('typeOverride', [
        'dataType' => 'string',
        'jsonType' => 'bool',
      ])
      ->setSetting('jsonPath', [
        'compensation',
        'compensationInfo',
        'generalInfoArray',
        'compensationPreviousYear',
      ])
      ->addConstraint('NotBlank');

    $info['compensation_total_amount'] = DataDefinition::create('float')
      // ->setRequired(TRUE)
      ->setLabel('compensationInfo=>purpose')
      ->setSetting('defaultValue', 0)
      ->setSetting('typeOverride', [
        'dataType' => 'string',
        'jsonType' => 'float',
      ])
      ->setSetting('jsonPath', [
        'compensation',
        'compensationInfo',
        'generalInfoArray',
        'totalAmount',
      ])
      ->addConstraint('NotBlank');

    $info['compensation_explanation'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('compensationInfo=>explanation')
      ->setSetting('defaultValue', "")
      ->setSetting('jsonPath', [
        'compensation',
        'compensationInfo',
        'generalInfoArray',
        'explanation',
        // ->addConstraint('NotBlank')
      ]);

    $info['myonnetty_avustus'] = ListDataDefinition::create('grants_metadata_other_compensation')
      // ->setRequired(TRUE)
      ->setLabel('Myönnetty avustus')
      ->setSetting('defaultValue', [])
      ->setSetting('jsonPath', [
        'compensation',
        'otherCompensationsInfo',
        'otherCompensationsArray',
      ])
      ->setSetting('requiredInJson', TRUE);

    $info['haettu_avustus_tieto'] = ListDataDefinition::create('grants_metadata_other_compensation')
      // ->setRequired(TRUE)
      ->setLabel('Haettu avustus')
      ->setSetting('defaultValue', [])
      ->setSetting('jsonPath', [
        'compensation',
        'otherCompensationsInfo',
        'otherAppliedCompensationsArray',
      ]);

    $info['myonnetty_avustus_total'] = DataDefinition::create('float')
      // ->setRequired(TRUE)
      ->setLabel('Myönnetty avustus total')
      ->setSetting('defaultValue', 0)
      ->setSetting('typeOverride', [
        'dataType' => 'string',
        'jsonType' => 'double',
      ])
      ->setSetting('valueCallback', [
        '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
        'convertToFloat',
      ])
      ->setSetting('jsonPath', [
        'compensation',
        'otherCompensationsInfo',
        'otherCompensationsInfoArray',
        'otherCompensationsTotal',
      ])
      ->addConstraint('NotBlank');

    $info['haettu_avustus_tieto_total'] = DataDefinition::create('float')
      // ->setRequired(TRUE)
      ->setLabel('Haettu avustus total')
      ->setSetting('defaultValue', 0)
      ->setSetting('typeOverride', [
        'dataType' => 'string',
        'jsonType' => 'double',
      ])
      ->setSetting('jsonPath', [
        'compensation',
        'otherCompensationsInfo',
        'otherCompensationsInfoArray',
        'otherAppliedCompensationsTotal',
      ])
      ->setSetting('valueCallback', [
        '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
        'convertToFloat',
      ])
      ->addConstraint('NotBlank');

    $info['benefits_loans'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('Loans')
      ->setSetting('defaultValue', "")
      ->setSetting('jsonPath', [
        'compensation',
        'benefitsInfoArray',
        'loans',
        // ->addConstraint('NotBlank')
      ]);

    $info['benefits_premises'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('Premises')
      ->setSetting('defaultValue', "")
      ->setSetting('jsonPath', [
        'compensation',
        'benefitsInfoArray',
        'premises',
        // ->addConstraint('NotBlank')
      ]);

    $info['fee_person'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('activitiesInfoArray=>feePerson')
      ->setSetting('jsonPath', [
        'compensation',
        'activitiesInfoArray',
        'feePerson',
      ])
      ->setSetting('valueCallback', [
        '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
        'convertToFloat',
      ])
      ->addConstraint('NotBlank');

    $info['fee_community'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('activitiesInfoArray=>feeCommunity')
      ->setSetting('jsonPath', [
        'compensation',
        'activitiesInfoArray',
        'feeCommunity',
      ])
      ->setSetting('valueCallback', [
        '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
        'convertToFloat',
      ])
      ->addConstraint('NotBlank');

    $info['members_applicant_person_local'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('activitiesInfoArray=>membersApplicantPersonLocal')
      ->setSetting('defaultValue', "")
      ->setSetting('jsonPath', [
        'compensation',
        'activitiesInfoArray',
        'membersApplicantPersonLocal',
        // ->addConstraint('NotBlank')
      ]);

    $info['members_applicant_person_global'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('activitiesInfoArray=>membersApplicantPersonGlobal')
      ->setSetting('defaultValue', "")
      ->setSetting('jsonPath', [
        'compensation',
        'activitiesInfoArray',
        'membersApplicantPersonGlobal',
        // ->addConstraint('NotBlank')
      ]);

    $info['members_applicant_community_local'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('activitiesInfoArray=>membersApplicantCommunityLocal')
      ->setSetting('defaultValue', "")
      ->setSetting('jsonPath', [
        'compensation',
        'activitiesInfoArray',
        'membersApplicantCommunityLocal',
        // ->addConstraint('NotBlank')
      ]);

    $info['members_applicant_community_global'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('activitiesInfoArray=>membersApplicantCommunityGlobal')
      ->setSetting('jsonPath', [
        'compensation',
        'activitiesInfoArray',
        'membersApplicantCommunityGlobal',
        // ->addConstraint('NotBlank')
      ]);

    $info['business_purpose'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('businessPurpose')
      ->setSetting('jsonPath', [
        'compensation',
        'activitiesInfoArray',
        'businessPurpose',
      ])
      ->setSetting('defaultValue', '');
    // ->addConstraint('NotBlank')
    $info['community_practices_business'] = DataDefinition::create('boolean')
      // ->setRequired(TRUE)
      ->setLabel('activitiesInfoArray=>communityPracticesBusiness')
      ->setSetting('defaultValue', FALSE)
      ->setSetting('jsonPath', [
        'compensation',
        'activitiesInfoArray',
        'communityPracticesBusiness',
      ])
      ->setSetting('typeOverride', [
        'dataType' => 'string',
        'jsonType' => 'bool',
      ])
      ->setSetting('defaultValue', FALSE);

    // ->addConstraint('NotBlank')
    $info['additional_information'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('additionalInformation')
      ->setSetting('jsonPath', ['compensation', 'additionalInformation'])
      ->setSetting('defaultValue', "");
    // ->addConstraint('NotBlank')
    // Sender details.
    // @todo Maybe move sender info to custom definition?
    $info['sender_firstname'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('firstname')
      ->setSetting('jsonPath', [
        'compensation',
        'senderInfoArray',
        'firstname',
      ]);
    // ->addConstraint('NotBlank')
    $info['sender_lastname'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('lastname')
      ->setSetting('jsonPath', [
        'compensation',
        'senderInfoArray',
        'lastname',
      ]);
    // ->addConstraint('NotBlank')
    // @todo Validate person id?
    $info['sender_person_id'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('personID')
      ->setSetting('jsonPath', [
        'compensation',
        'senderInfoArray',
        'personID',
      ]);
    // ->addConstraint('NotBlank')
    $info['sender_user_id'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('userID')
      ->setSetting('jsonPath', ['compensation', 'senderInfoArray', 'userID']);
    // ->addConstraint('NotBlank')
    $info['sender_email'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('Email')
      ->setSetting('jsonPath', ['compensation', 'senderInfoArray', 'email']);

    // Attachments.
    $info['attachments'] = ListDataDefinition::create('grants_metadata_attachment')
      // ->setRequired(TRUE)
      ->setLabel('Attachments')
      ->setSetting('jsonPath', ['attachmentsInfo', 'attachmentsArray']);

    $info['extra_info'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
      ->setLabel('Extra Info')
      ->setSetting('jsonPath', [
        'attachmentsInfo',
        'generalInfoArray',
        'extraInfo',
      ]);

    $info['form_update'] = DataDefinition::create('boolean')
      ->setRequired(TRUE)
      ->setLabel('formUpdate')
      ->setSetting('jsonPath', ['formUpdate'])
      ->setSetting('typeOverride', [
        'dataType' => 'string',
        'jsonType' => 'bool',
      ])
      ->setSetting('defaultValue', FALSE);

    $info['status_updates'] = MapDataDefinition::create()
      ->setPropertyDefinition(
        'caseId',
        DataDefinition::create('string')
          ->setRequired(TRUE)
          ->setSetting('jsonPath', ['statusUpdates', 'caseId'])
      )
      ->setPropertyDefinition(
        'citizenCaseStatus',
        DataDefinition::create('string')
          ->setRequired(TRUE)
          ->setSetting('jsonPath', ['statusUpdates', 'citizenCaseStatus'])
      )
      ->setPropertyDefinition(
        'eventType',
        DataDefinition::create('string')
          ->setRequired(TRUE)
          ->setSetting('jsonPath', ['statusUpdates', 'eventType'])
      )
      ->setPropertyDefinition(
        'eventCode',
        DataDefinition::create('integer')
          ->setRequired(TRUE)
          ->setSetting('jsonPath', ['statusUpdates', 'eventCode'])
      )
      ->setPropertyDefinition(
        'eventSource',
        DataDefinition::create('string')
          ->setRequired(TRUE)
          ->setSetting('jsonPath', ['statusUpdates', 'eventSource'])
      )
      ->setSetting('jsonPath', ['statusUpdates']);

    return $info;
  }

}
