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
    //const CLASS_STORAGE_XML = 'data_text5';

    protected $defaultLimit = 0; // Max items to be selected

    function __construct() {
        parent::eZDataType( self::DATA_TYPE_STRING, ezpI18n::tr( 'kernel/classes/datatypes', "Enhanced relation list (OW)", 'Datatype name' ),
          array( 'serialize_supported' => true ) );
    }

    /*     * ******
     * CLASS *
     * ****** */

    static function contentObjectArrayXMLMap() {
        $array = parent::contentObjectArrayXMLMap();
        return array_merge($array, array(
            'limit' => 'limit',
        ));
    }

    function objectAttributeContent( $contentObjectAttribute )
    {
        $xmlText = $contentObjectAttribute->attribute( 'data_text' );
        if ( trim( $xmlText ) == '' )
        {
            $objectAttributeContent = $this->defaultObjectAttributeContent();
            return $objectAttributeContent;
        }
        $doc = $this->parseXML( $xmlText );
        $content = $this->createObjectContentStructure( $doc );

        return $content;
    }

    function appendObject( $objectID, $priority, $contentObjectAttribute, $limit = '0' ) {
        $relationItem = parent::appendObject($objectID, $priority, $contentObjectAttribute);
        $relationItem['limit'] = intval($limit);

        return $relationItem;
    }

    function fetchClassAttributeHTTPInput( $http, $base, $classAttribute )
    {
        parent::fetchClassAttributeHTTPInput( $http, $base, $classAttribute );

        $content = $classAttribute->content();

        $limitVariable = 'ContentClass_ezobjectrelationlist_limit_' . $classAttribute->attribute( 'id' );
        if ( $http->hasPostVariable( $limitVariable ) )
        {
            $limit = $http->postVariable( $limitVariable );
            $content['limit'] = intval($limit);
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
        $limit = $doc->createElement( 'limit' );
        $limit->setAttribute( 'value', $content['limit'] );
        $root->appendChild( $limit );
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
        return array( 'object_class' => '',
          'selection_type' => 0,
          'type' => 0,
          'limit' => $this->defaultLimit,
          'class_constraint_list' => array(),
          'default_placement' => false );
    }

    function createClassContentStructure( $doc )
    {
        $content = parent::createClassContentStructure($doc);
        $root = $doc->documentElement;

        $limit = $root->getElementsByTagName( 'limit' )->item( 0 );
        if ( $limit ) {
            $content['limit'] = $limit->getAttribute( 'value' );
        }

        return $content;
    }

    function serializeContentClassAttribute( $classAttribute, $attributeNode, $attributeParametersNode )
    {
        parent::serializeContentClassAttribute( $classAttribute, $attributeNode, $attributeParametersNode );

        $dom = $attributeParametersNode->ownerDocument;
        $content = $classAttribute->content();

        $limit = is_numeric( $content['limit'] ) ? $content['limit'] : $this->defaultLimit;
        $limitNode = $dom->createElement( 'limit' );
        $limitNode->appendChild( $dom->createTextNode( $limit ) );
        $attributeParametersNode->appendChild( $limitNode );
    }

    function unserializeContentClassAttribute( $classAttribute, $attributeNode, $attributeParametersNode )
    {
        parent:unserializeContentClassAttribute( $classAttribute, $attributeNode, $attributeParametersNode );

        $content = $classAttribute->content();
        $content['limit'] = $attributeParametersNode->getElementsByTagName( 'limit' )->item( 0 )->textContent;
        $classAttribute->setContent( $content );
        $this->storeClassAttributeContent( $classAttribute, $content );
    }

}

eZDataType::register( OWEnhancedRelationListType::DATA_TYPE_STRING, "OWEnhancedRelationListType" );

?>