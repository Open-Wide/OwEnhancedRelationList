<?php

class OWEnhancedRelationListTypeMigrationHandler extends eZObjectRelationListTypeMigrationHandler {

    static public function toArray( eZContentClassAttribute $attribute ) {
        $attributesArray = parent::toArray( $attribute );
        foreach ( $attribute->content() as $attributeIdentifier => $attributeValue ) {
            switch ( $attributeIdentifier ) {
                case 'min_elements' :
                case 'max_elements' :
                    $attributesArray[$attributeIdentifier] = $attributeValue;
                    break;
                default :
                    break;
            }
        }
        return $attributesArray;
    }

    static public function fromArray( eZContentClassAttribute $attribute, array $options ) {
        parent::fromArray( $attribute, $options );
        $content = $attribute->content();
        foreach ( $options as $optionIdentifier => $optionValue ) {
            switch ( $optionIdentifier ) {
                case 'min_elements' :
                case 'max_elements' :
                    $content[$optionIdentifier] = $optionValue;
                    break;
                default :
                    break;
            }
        }
        $attribute->setContent( $content );
    }

}
