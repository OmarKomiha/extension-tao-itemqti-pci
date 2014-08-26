<?php
/*
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * Copyright (c) 2013 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *               
 * 
 */

namespace oat\qtiItemPci\model;

use \core_kernel_classes_Resource;
use \core_kernel_classes_Class;
use \core_kernel_classes_Property;
use \tao_models_classes_service_FileStorage;
use oat\taoQtiItem\helpers\QtiPackage;
use \ZipArchive;

/**
 * The hook used in the item creator
 *
 * @package qtiItemPci
 */
class PciCreatorRegistry
{

    const REGISTRY_URI = 'http://www.tao.lu/Ontologies/QtiItemPci.rdf#PciCreatorHook';

    /**
     * @var tao_models_classes_service_FileStorage
     */
    private static $instance;

    /**
     * @return tao_models_classes_service_FileStorage
     */
    public static function singleton(){

        if(is_null(self::$instance)){
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct(){

        $this->registryClass = new core_kernel_classes_Class('http://www.tao.lu/Ontologies/QtiItemPci.rdf#PciCreatorHook');
        $this->storage = tao_models_classes_service_FileStorage::singleton();
        $this->propIdentifier = new core_kernel_classes_Property('http://www.tao.lu/Ontologies/QtiItemPci.rdf#PciCreatorIdentifier');
        $this->propDirectory = new core_kernel_classes_Property('http://www.tao.lu/Ontologies/QtiItemPci.rdf#PciCreatorDirectory');
    }

    public function add($id, $archive){

        $returnValue = null;

        if($this->isValid($archive)){

            $directory = $this->storage->spawnDirectory(true);
            $directoryId = $directory->getId();

            //obtain the id:
            $typeIdentifier = 'aPci';

            //copy content in the directory:
            $this->storage->import($directoryId, 'path');

            $returnValue = $this->registryClass->createInstanceWithProperties(array(
                $this->propIdentifier->getUri() => $typeIdentifier,
                $this->propDirectory->getUri() => $directoryId
            ));
        }else{
            throw new \common_Exception('invalid PCI creator archive');
        }

        return $returnValue;
    }

    public function getAll(){

        $returnValue = array();

        $all = $this->registryClass->getInstances();
        foreach($all as $pci){
            $pciData = $this->getData($pci);
            $returnValue[$pciData['typeIdentifier']] = $pciData;
        }
        
        return $returnValue;
    }

    public function remove($id){
        
    }

    protected function getResource($id){
        $resources = $this->registryClass->searchInstances(array($this->propIdentifier->getUri() => $id));
        return reset($resources);
    }

    protected function getData(core_kernel_classes_Resource $hook){
        return array(
            'typeIdentifier' => $hook->getUniquePropertyValue($this->propIdentifier),
            'directory' => $hook->getUniquePropertyValue($this->propDirectory)
        );
    }

    public function get($id){

        $returnValue = null;
        $hook = $this->getResource($id);

        if(!$hook){
            $returnValue = $this->getData($hook);
        }

        return $returnValue;
    }

    public function isValid($archive){
        
        $returnValue = false;

        if(QtiPackage::isValidZip($archive)){

            $zip = new ZipArchive();
            $zip->open($archive, ZIPARCHIVE::CHECKCONS);
            if($zip->locateName("pciCreator.json") === false){
                throw new Exception("A PCI creator package must contains a pciCreator.json file at the root of the archive");
            }else if($zip->locateName("pciCreator.js") === false){
                throw new Exception("A PCI creator package must contains a pciCreator.js file at the root of the archive");
            }else{
                $returnValue = true;
            }

            $zip->close();
        }

        return $returnValue;
    }

}