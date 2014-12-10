<?php

class OWEnhancedRelationListSolrDocumentField extends ezfSolrDocumentFieldObjectRelation {

    /**
     * @deprecated since eZ Find 2.1
     * Get collection data. Returns list of ezfSolrDocumentFieldBase documents.
     *
     * @return array List of ezfSolrDocumentFieldBase objects.
     */
    public function getCollectionData() {
        $returnList = array();
        switch ( $this->ContentObjectAttribute->attribute( 'data_type_string' ) ) {
            case 'owenhancedrelationlist': {
                    $content = $this->ContentObjectAttribute->content();
                    foreach ( $content['relation_list'] as $relationItem ) {
                        $subObjectID = $relationItem['contentobject_id'];
                        if ( !$subObjectID )
                            continue;
                        $subObject = eZContentObjectVersion::fetchVersion( $relationItem['contentobject_version'], $subObjectID );
                        if ( !$subObject )
                            continue;

                        $returnList = array_merge( $this->getBaseList( $subObject ), $returnList );
                    }
                } break;
        }

        return $returnList;
    }

    /**
     * @see ezfSolrDocumentFieldBase::getData()
     */
    public function getData() {
        $contentClassAttribute = $this->ContentObjectAttribute->attribute( 'contentclass_attribute' );

        switch ( $contentClassAttribute->attribute( 'data_type_string' ) ) {
            case 'owenhancedrelationlist' : {
                    $returnArray = array();
                    $content = $this->ContentObjectAttribute->content();

                    foreach ( $content['relation_list'] as $relationItem ) {
                        $subObjectID = $relationItem['contentobject_id'];
                        if ( !$subObjectID )
                            continue;

                        // Using last version of object (version inside xml data is the original version)
                        $subObject = eZContentObject::fetch( $subObjectID );

                        if ( !$subObject || $relationItem['in_trash'] )
                            continue;

                        // 1st create aggregated metadata fields
                        $metaAttributeValues = eZSolr::getMetaAttributesForObject( $subObject );
                        foreach ( $metaAttributeValues as $metaInfo ) {
                            $submetaFieldName = ezfSolrDocumentFieldBase::generateSubmetaFieldName( $metaInfo['name'], $contentClassAttribute );
                            if ( isset( $returnArray[$submetaFieldName] ) ) {
                                $returnArray[$submetaFieldName] = array_merge( $returnArray[$submetaFieldName], array( ezfSolrDocumentFieldBase::preProcessValue( $metaInfo['value'], $metaInfo['fieldType'] ) ) );
                            } else {
                                $returnArray[$submetaFieldName] = array( ezfSolrDocumentFieldBase::preProcessValue( $metaInfo['value'], $metaInfo['fieldType'] ) );
                            }
                        }
                    }

                    $defaultFieldName = parent::generateAttributeFieldName( $contentClassAttribute, self::$subattributesDefinition[self::DEFAULT_SUBATTRIBUTE] );
                    $returnArray[$defaultFieldName] = $this->getPlainTextRepresentation();
                    return $returnArray;
                }
                break;
            default: {
                    
                } break;
        }
    }

    protected function getPlainTextRepresentation( eZContentObjectAttribute $contentObjectAttribute = null ) {
        if ( $contentObjectAttribute === null ) {
            $contentObjectAttribute = $this->ContentObjectAttribute;
        }

        $metaData = '';

        if ( $contentObjectAttribute ) {
            $metaDataArray = $contentObjectAttribute->metaData();
            if ( !is_array( $metaDataArray ) ) {
                $metaDataArray = array( $metaDataArray );
            }

            foreach ( $metaDataArray as $item ) {
                if ( isset( $item['text'] ) ) {
                    $metaData .= $item['text'] . ' ';
                }
            }
        }
        return trim( $metaData, "\t\r\n " );
    }

}
