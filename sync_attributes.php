<!--  SYNC PRODUCT ATTRIBUTES FROM MAGENTO STORE -->

<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '5G');
ini_set('max_execution_time', '0');

error_reporting(E_ALL);

use Magento\Framework\App\Bootstrap;
require '/home/site/public_html/app/bootstrap.php';


$bootstrap = Bootstrap::create(BP, $_SERVER);

$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');

$registry = $objectManager->get('\Magento\Framework\Registry');
$registry->register('isSecureArea', true);

$userData = array("username" => "", "password" => ""); // Login details of source Magento si5te
$url = ""; //site url

$ch = curl_init("$url/index.php/rest/all/V1/integration/admin/token");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($userData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Content-Length: " . strlen(json_encode($userData))));

$token = curl_exec($ch);

$localAttributes = getLocalAttributes();
$baseAttributes = getBaseAttributes($url, $token);


syncGroups($localAttributes, $baseAttributes);
syncAttributes($localAttributes, $baseAttributes);


function createAttributes($att, $allLocals){

	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
	$state = $objectManager->get('Magento\Framework\App\State');
	$state->setAreaCode('frontend');

	$eavSetup = $objectManager->create(\Magento\Eav\Setup\EavSetup::class);

	foreach($att as $a){
		$code = $a['code'];
		$order = $a['order'];
		$label = $a['label'];
		$group = $a['group'];
		$input = $a['frontend_input'];

		$is_wysiwyg_enabled = $a['is_wysiwyg_enabled'];
		$is_html_allowed_on_front = $a['is_html_allowed_on_front'];
		$used_for_sort_by = $a['used_for_sort_by'];
		$is_filterable = $a['is_filterable'];
		$is_filterable_in_search = $a['is_filterable_in_search'];
		$is_used_in_grid = $a['is_used_in_grid'];
		$is_visible_in_grid = $a['is_visible_in_grid'];
		$is_filterable_in_grid = $a['is_filterable_in_grid'];
		$position = $a['position'];
		$apply_to = $a['apply_to'];
		$is_searchable = $a['is_searchable'];
		$is_visible_in_advanced_search = $a['is_visible_in_advanced_search'];
		$is_comparable = $a['is_comparable'];
		// $is_used_for_promo_rules = $a['is_used_for_promo_rules'];
		$is_visible_on_front = $a['is_visible_on_front'];
		$used_in_product_listing = $a['used_in_product_listing'];
		$is_visible = $a['is_visible'];
		$scope = $a['scope'];
		$is_required = $a['is_required'];
		$options = $a['options'];
		$is_user_defined = $a['is_user_defined'];
		$backend_type = $a['backend_type'];
		$is_unique = $a['is_unique'];
		$validation_rules = $a['validation_rules'];

		$groupid = "";


		foreach($allLocals as $local){
			if($local['name'] == $a['group']){
				$groupid = $local['id'];
				break;
			}
		}

		$eavAttribute = $objectManager->create('Magento\Eav\Model\ResourceModel\Entity\Attribute');
		$attributeId = $eavAttribute->getIdByCode('catalog_product', $code);

		try {
			if($attributeId == ""){
				$eavSetup->addAttribute(
				    \Magento\Catalog\Model\Product::ENTITY,
				    $code,
				    [
				        'user_defined' => $is_user_defined,
				        'type' => $backend_type,
				        'label' => $label,
				        'input' => $input,
				        'required' => $is_required,
				        'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
				        'used_in_product_listing' => $used_in_product_listing,
				        'visible_on_front' => $is_visible,
				        'is_filterable' => $is_filterable,
				        'is_filterable_in_grid' => $is_filterable_in_grid
				    ]
				);

				echo "\nadding... code: ".$code."\n";
				// echo "label: ".$label."\n";
				// echo "order: ".$order."\n";
				// echo "group: ".$group."\n";
			} else {
				$eavSetup->updateAttribute(
				    \Magento\Catalog\Model\Product::ENTITY,
				    $code,
				    [
				        'user_defined' => $is_user_defined,
				        'type' => $backend_type,
				        'label' => $label,
				        'input' => $input,
				        'required' => $is_required,
				        'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
				        'used_in_product_listing' => $used_in_product_listing,
				        'visible_on_front' => $is_visible,
				        'is_filterable' => $is_filterable,
				        'is_filterable_in_grid' => $is_filterable_in_grid
				    ]
				);

				echo "\nupdating... code: ".$code."<BR>";
				// echo "label: ".$label."\n";
				// echo "order: ".$order."\n";
				// echo "group: ".$group."\n";

			}

			$eavAttribute2 = $objectManager->create('Magento\Eav\Model\Config');
	        $attribute = $eavAttribute2->getAttribute('catalog_product', $code);
	        $options3 = $attribute->getSource()->getAllOptions();

	

			if(sizeof($options)>0){
				$attributeId = $eavAttribute->getIdByCode('catalog_product', $code);
		
				$opt_arr = array();
				foreach($options as $o){
					if($o['label'] != ' '){
						array_push($opt_arr,$o['label']);
					}
				}

				$options2 = [
				    'values' => $opt_arr,
				    'attribute_id' => $attributeId,
				];

				$eavSetup->addAttributeOption($options2);
			}

			$eavSetup->addAttributeToGroup(
			    \Magento\Catalog\Model\Product::ENTITY,
			    'Default',
			    $group,  // group
			    $code,  // attribute code
			    $order // sort order
			);

		} catch (Exception $e) {
		    echo $e->getMessage();
		}
	}
}

function syncGroups($local,$base){

	$bootstrap = Bootstrap::create(BP, $_SERVER);
	$objectManager = $bootstrap->getObjectManager();
	
	foreach($base as $batt){
	
		$attributeGroupCode = $batt['name'];
		
		$config= $objectManager->get('Magento\Catalog\Model\Config');

		$attributeGroupRepository = $objectManager->get('Magento\Eav\Api\AttributeGroupRepositoryInterface');

        $attributeGroupId = $config->getAttributeGroupId(4, $attributeGroupCode);
  		// echo $attributeGroupCode.":";
		// echo $attributeGroupId."\n"; 

		if($attributeGroupId == ""){
			$attributeGroup = $objectManager->get('Magento\Eav\Api\Data\AttributeGroupInterfaceFactory')->create();
	        $attributeGroup->setAttributeSetId(4);
	        $attributeGroup->setAttributeGroupName($attributeGroupCode);
	        $attributeGroupRepository->save($attributeGroup);
		}
	}

	foreach($local as $latt){
		$exists = false;

		foreach($base as $batt){

			if($latt['name'] == $batt['name']){
				$exists = true;
			}
		}

		if(!$exists){
			echo "deleting... code: ".$latt['name']."<BR>";

			$attributeGroupCode = $latt['name'];
			$config = $objectManager->get('Magento\Catalog\Model\Config');
			$attributeGroupRepository = $objectManager->get('Magento\Eav\Api\AttributeGroupRepositoryInterface');
        	$attributeGroupId = $config->getAttributeGroupId(4, $attributeGroupCode);
			$attributeGroupRepository->deleteById($attributeGroupId);

		}
	}
}


function syncAttributes($local, $base){
	global $co;
	$new = false;
	$del = false;
	$tochange = array();
	$tocreate = array();
	foreach($base as $batt){
		foreach($batt['atts'] as $b){
			$atts = array();
			$exists = false;
			$changed = false;
			
		
				$b['group'] = $batt['name'];
				array_push($tocreate,$b);
		
		}
	}



	if(sizeof($tocreate) > 0){
		createAttributes($tocreate, $local);

	} else {
		echo "\nNothing added\n";
	}
	

}


function getBaseAttributes($url, $token){
	$baseatts = array();

	
	$ch = curl_init("$url/index.php/rest/all/V1/products/attribute-sets/groups/list?searchCriteria[filter_groups][0][filters][0][field]=attribute_set_id&searchCriteria[filter_groups][0][filters][0][value]=4&searchCriteria[filter_groups][0][filters][0][condition_type]=eq");

	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer " . json_decode($token)));

	$result = curl_exec($ch);
	$json = json_decode($result, true);
	
	$level = 0;

	foreach($json['items'] as $attgrp){
		$attgrps = array();
		$name = $attgrp['attribute_group_name'];
		$grpid = $attgrp['attribute_group_id'];
		$level++;
		$position = $level;
		$attgrps['id'] = $attgrp['attribute_group_id'];
		$attgrps['name'] = $name;
		$attgrps['order'] = $position;
		$attgrps['atts'] = array();
		
		$ch2 = curl_init("$url/index.php/rest/all/V1/products/attributes/?searchCriteria[filter_groups][0][filters][0][field]=attribute_group_id&searchCriteria[filter_groups][0][filters][0][value]=$grpid&searchCriteria[filter_groups][0][filters][0][condition_type]=eq&searchCriteria[filter_groups][1][filters][0][field]=is_user_defined&searchCriteria[filter_groups][1][filters][0][value]=1&searchCriteria[filter_groups][1][filters][0][condition_type]=eq");

		curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch2, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer " . json_decode($token)));
		
		$result2 = curl_exec($ch2);
		$json2 = json_decode($result2, true);

		$level2 = 0;

		foreach($json2['items'] as $atts){

			$attrs = array();
			$level2++;
			$attrs['label'] = $atts['default_frontend_label'];
			$attrs['code'] = $atts['attribute_code'];
			$attrs['frontend_input'] = $atts['frontend_input'];
			$attrs['is_wysiwyg_enabled'] = $atts['is_wysiwyg_enabled'];
			$attrs['is_html_allowed_on_front'] = $atts['is_html_allowed_on_front'];
			$attrs['used_for_sort_by'] = $atts['used_for_sort_by'];
			$attrs['is_filterable'] = $atts['is_filterable'];
			$attrs['is_filterable_in_search'] = $atts['is_filterable_in_search'];
			$attrs['is_used_in_grid'] = $atts['is_used_in_grid'];
			$attrs['is_visible_in_grid'] = $atts['is_visible_in_grid'];
			$attrs['is_filterable_in_grid'] = $atts['is_filterable_in_grid'];
			$attrs['position'] = $atts['position'];
			$attrs['apply_to'] = $atts['apply_to'];
			$attrs['is_searchable'] = $atts['is_searchable'];
			$attrs['is_visible_in_advanced_search'] = $atts['is_visible_in_advanced_search'];
			$attrs['is_comparable'] = $atts['is_comparable'];
			$attrs['is_used_for_promo_rules'] = $atts['is_used_for_promo_rules'];
			$attrs['is_visible_on_front'] = $atts['is_visible_on_front'];
			$attrs['used_in_product_listing'] = $atts['used_in_product_listing'];
			$attrs['is_visible'] = $atts['is_visible'];
			$attrs['scope'] = $atts['scope'];
			$attrs['is_required'] = $atts['is_required'];
			$attrs['options'] = $atts['options'];
			$attrs['is_user_defined'] = $atts['is_user_defined'];
			$attrs['backend_type'] = $atts['backend_type'];
			$attrs['is_unique'] = $atts['is_unique'];
			$attrs['validation_rules'] = $atts['validation_rules'];

			$attrs['order'] = $level2;

			if($attrs['code'] != 'country_of_manufacture') {
				array_push($attgrps['atts'], $attrs);

				// echo "\noptions\n";
				// print_r($attrs['options']);
				// echo "\n--------\n";
			}

		}

		array_push($baseatts, $attgrps);
	}

	return $baseatts;
}


function getLocalAttributes(){
	$bootstrap = Bootstrap::create(BP, $_SERVER);

	$objectManager = $bootstrap->getObjectManager();

	$col = $objectManager->create('\Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory');
	$cole = $objectManager->create('\Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory');

	$attributeGroupCollection = $col->create()
	        ->setAttributeSetFilter(4)
	        ->setOrder('sort_order', 'ASC')
	        ->load(); // product attribute group collection

	$attGrpArr = array();

	foreach ($attributeGroupCollection as $attributeGroup){
	    if (strpos($attributeGroup->getData('attribute_group_name'), 'Specifications') !== false) {
	        $attGrp = array();
	        $attGrp['name'] = $attributeGroup->getData('attribute_group_name');
	        $attGrp['atts'] = array();
	        $attGrp['id'] = $attributeGroup->getData('attribute_group_id');

	        $attributeCollection = $cole->create()
	            ->setAttributeGroupFilter($attributeGroup->getId())
	            ->addVisibleFilter()
	            ->addFieldToFilter('is_user_defined', array('eq' => 1))
	            ->load();

	        foreach ($attributeCollection as $attribute){
	            $attArr = array();
	            $attArr['label'] = $attribute->getData('frontend_label');
	            $attArr['order'] = $attribute->getData('sort_order');
	            $attArr['code'] = $attribute->getData('attribute_code'); 

	            array_push($attGrp['atts'], $attArr);
	        }

			array_push($attGrpArr, $attGrp);

	    }
	}

	return $attGrpArr;

}