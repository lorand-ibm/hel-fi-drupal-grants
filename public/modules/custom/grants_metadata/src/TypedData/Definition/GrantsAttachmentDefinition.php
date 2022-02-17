<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Define Application official data.
 */
class GrantsAttachmentDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(): array {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['description'] = DataDefinition::create('string')
        ->setLabel('description')
        ->setSetting('jsonPath', [
          'attachmentsInfo',
          'attachmentsArray',
          'description',
        ]);

      $info['fileName'] = DataDefinition::create('string')
      // ->setRequired(TRUE)
        ->setLabel('File name.')
        ->setSetting('jsonPath', [
          'attachmentsInfo',
          'attachmentsArray',
          'fileName',
        ]);

      $info['fileType'] = DataDefinition::create('integer')
      // ->setRequired(TRUE)
        ->setLabel('File type.')
        ->setSetting('jsonPath', [
          'attachmentsInfo',
          'attachmentsArray',
          'fileType',
        ]);

      $info['integrationID'] = DataDefinition::create('string')
      // ->setRequired(FALSE)
        ->setLabel('Integration ID')
        ->setSetting('jsonPath', [
          'attachmentsInfo',
          'attachmentsArray',
          'integrationID',
        ]);

      $info['isDeliveredLater'] = DataDefinition::create('boolean')
      // ->setRequired(TRUE)
        ->setLabel('Is delivered later')
        ->setSetting('jsonPath', [
          'attachmentsInfo',
          'attachmentsArray',
          'isDeliveredLater',
        ]);

      $info['isIncludedInOtherFile'] = DataDefinition::create('boolean')
      // ->setRequired(TRUE)
        ->setLabel('In in other attachment')
        ->setSetting('jsonPath', [
          'attachmentsInfo',
          'attachmentsArray',
          'isIncludedInOtherFile',
        ]);

      $info['isNewAttachment'] = DataDefinition::create('boolean')
      // ->setRequired(TRUE)
        ->setLabel('Attachment is new')
        ->setSetting('jsonPath', [
          'attachmentsInfo',
          'attachmentsArray',
          'isNewAttachment',
        ]);

    }
    return $this->propertyDefinitions;
  }

}
