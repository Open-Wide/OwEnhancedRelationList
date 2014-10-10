<?php

/*
  Enhanced selection extension for eZ publish 4.x
  Copyright (C) 2003-2008  SCK-CEN (Belgian Nuclear Research Centre)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.
 */


/* !
  \class   OWEnhancedRelationListType owenhancedrelationlisttype.php
  \ingroup eZDatatype
  \brief   Handles the datatype owenhancedrelationList.
  \version 1.0
  \date    Thursday 9 October 2014 18:00:00 am
  \author  Fabien Jarnet
 */

class OWEnhancedRelationListType extends eZObjectRelationListType {

    const DATA_TYPE_STRING = 'owenhancedrelationlist';

    protected $defaultMinElements = 0; // Min items to be selected
    protected $defaultMaxElements = 0; // Max items to be selected

    function __construct() {
        parent::eZDataType( self::DATA_TYPE_STRING, ezpI18n::tr( 'kernel/classes/datatypes', "Enhanced relation list (OW)", 'Datatype name' ),
          array( 'serialize_supported' => true ) );
    }

    /*
     * OBJECT
     */
    function validateObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {

        $status = parent::validateObjectAttributeHTTPInput($http, $base, $contentObjectAttribute);

        if ($status == eZInputValidator::STATE_INVALID) {
            return $status;
        }

        if ($http->hasPostVariable('PublishButton') || $http->hasPostVariable('StoreExitButton')) {

            $contentClassAttribute = $contentObjectAttribute->contentClassAttribute();
            $classContent = $contentClassAttribute->content();
            $content = $contentObjectAttribute->content();
            $countRelationList = count($content['relation_list']);

            if ($classContent['min_elements'] > 0) {

                // Enough elements ?
                if ($countRelationList < $classContent['min_elements']) {
                    $contentObjectAttribute->setValidationError(
                      ezpI18n::tr(
                        'kernel/classes/datatypes',
                        'Must have more than %XXX% objects',
                        null,
                        array(
                          '%XXX%' => $classContent['min_elements'],
                        )
                      )
                    );
                    return eZInputValidator::STATE_INVALID;
                }
            }
            if ($classContent['max_elements'] > 0) {
                // Too much elements ?
                if ($countRelationList > $classContent['max_elements']) {
                    $contentObjectAttribute->setValidationError(
                      ezpI18n::tr(
                        'kernel/classes/datatypes',
                        'Must have less than %XXX% objects',
                        null,
                        array(
                          '%XXX%' => $classContent['max_elements'],
                        )
                      )
                    );
                    return eZInputValidator::STATE_INVALID;
                }
            }
        }
        return $status;
    }

    /*
     * CLASS
     */

    static function contentObjectArrayXMLMap() {
        $array = parent::contentObjectArrayXMLMap();
        return array_merge($array, array(
            'min_elements' => 'min_elements',
            'max_elements' => 'max_elements',
        ));
    }

    function appendObject( $objectID, $priority, $contentObjectAttribute, $min_elements = 0, $max_elements = 0 ) {
        $relationItem = parent::appendObject($objectID, $priority, $contentObjectAttribute);
        $relationItem['min_elements'] = intval($min_elements);
        $relationItem['max_elements'] = intval($max_elements);
        return $relationItem;
    }

    function fetchClassAttributeHTTPInput( $http, $base, $classAttribute )
    {
        parent::fetchClassAttributeHTTPInput( $http, $base, $classAttribute );

        $content = $classAttribute->content();

        $minElementsVariable = 'ContentClass_ezobjectrelationlist_min_elements_' . $classAttribute->attribute( 'id' );
        if ( $http->hasPostVariable( $minElementsVariable ) )
        {
            $min_elements = $http->postVariable( $minElementsVariable );
            $content['min_elements'] = intval($min_elements);
        }
        $maxElementsVariable = 'ContentClass_ezobjectrelationlist_max_elements_' . $classAttribute->attribute( 'id' );
        if ( $http->hasPostVariable( $maxElementsVariable ) )
        {
            $max_elements = $http->postVariable( $maxElementsVariable );
            $content['max_elements'] = intval($max_elements);
        }

        $classAttribute->setContent( $content );
        $classAttribute->store();
        return true;
    }

    static function createClassDOMDocument( $content )
    {
        $doc = new DOMDocument( '1.0', 'utf-8' );
        $root = $doc->createElement( 'related-objects' );
        $constraints = $doc->createElement( 'constraints' );
        foreach ( $content['class_constraint_list'] as $constraintClassIdentifier )
        {
            unset( $constraintElement );
            $constraintElement = $doc->createElement( 'allowed-class' );
            $constraintElement->setAttribute( 'contentclass-identifier', $constraintClassIdentifier );
            $constraints->appendChild( $constraintElement );
        }
        $root->appendChild( $constraints );
        $constraintType = $doc->createElement( 'type' );
        $constraintType->setAttribute( 'value', $content['type'] );
        $root->appendChild( $constraintType );

        $min_elements = $doc->createElement( 'min_elements' );
        $min_elements->setAttribute( 'value', $content['min_elements'] );
        $root->appendChild( $min_elements );

        $max_elements = $doc->createElement( 'max_elements' );
        $max_elements->setAttribute( 'value', $content['max_elements'] );
        $root->appendChild( $max_elements );

        $selectionType = $doc->createElement( 'selection_type' );
        $selectionType->setAttribute( 'value', $content['selection_type'] );
        $root->appendChild( $selectionType );
        $objectClass = $doc->createElement( 'object_class' );
        $objectClass->setAttribute( 'value', $content['object_class'] );
        $root->appendChild( $objectClass );

        $placementNode = $doc->createElement( 'contentobject-placement' );
        if ( $content['default_placement'] )
        {
            $placementNode->setAttribute( 'node-id',  $content['default_placement']['node_id'] );
        }
        $root->appendChild( $placementNode );
        $doc->appendChild( $root );
        return $doc;
    }

    function defaultClassAttributeContent()
    {
        return array(
            'object_class' => '',
            'selection_type' => 0,
            'type' => 0,
            'min_elements' => $this->defaultMinElements,
            'max_elements' => $this->defaultMaxElements,
            'class_constraint_list' => array(),
            'default_placement' => false,
        );
    }

    function createClassContentStructure( $doc )
    {
        $content = parent::createClassContentStructure($doc);
        $root = $doc->documentElement;

        $min_elements = $root->getElementsByTagName( 'min_elements' )->item( 0 );
        if ( $min_elements ) {
            $content['min_elements'] = $min_elements->getAttribute( 'value' );
        }
        $max_elements = $root->getElementsByTagName( 'max_elements' )->item( 0 );
        if ( $max_elements ) {
            $content['max_elements'] = $max_elements->getAttribute( 'value' );
        }
        return $content;
    }

    function serializeContentClassAttribute( $classAttribute, $attributeNode, $attributeParametersNode )
    {
        parent::serializeContentClassAttribute( $classAttribute, $attributeNode, $attributeParametersNode );

        $dom = $attributeParametersNode->ownerDocument;
        $content = $classAttribute->content();

        $min_elements = is_numeric( $content['min_elements'] ) ? $content['min_elements'] : $this->defaultMinElements;
        $min_elementsNode = $dom->createElement( 'min_elements' );
        $min_elementsNode->appendChild( $dom->createTextNode( $min_elements ) );
        $attributeParametersNode->appendChild( $min_elementsNode );

        $max_elements = is_numeric( $content['max_elements'] ) ? $content['max_elements'] : $this->defaultMaxElements;
        $max_elementsNode = $dom->createElement( 'max_elements' );
        $max_elementsNode->appendChild( $dom->createTextNode( $max_elements ) );
        $attributeParametersNode->appendChild( $max_elementsNode );
    }

    function unserializeContentClassAttribute( $classAttribute, $attributeNode, $attributeParametersNode )
    {
        parent:unserializeContentClassAttribute( $classAttribute, $attributeNode, $attributeParametersNode );

        $content = $classAttribute->content();
        $content['min_elements'] = $attributeParametersNode->getElementsByTagName( 'min_elements' )->item( 0 )->textContent;
        $content['max_elements'] = $attributeParametersNode->getElementsByTagName( 'max_elements' )->item( 0 )->textContent;
        $classAttribute->setContent( $content );
        $this->storeClassAttributeContent( $classAttribute, $content );
    }

}

eZDataType::register( OWEnhancedRelationListType::DATA_TYPE_STRING, "OWEnhancedRelationListType" );

?>