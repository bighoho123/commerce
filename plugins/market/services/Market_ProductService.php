<?php

namespace Craft;

/**
 * Class Market_ProductService
 *
 * @package Craft
 */
class Market_ProductService extends BaseApplicationComponent
{
	/**
	 * @param int $id
	 * @return Market_ProductModel
	 */
	public function getById($id)
	{
        return craft()->elements->getElementById($id, 'Market_Product');
	}

	/**
	 * @param Market_ProductModel $product
	 *
	 * @return bool
	 * @throws \CDbException
	 */
	public function delete($product)
	{
		$product = Market_ProductRecord::model()->findById($product->id);
		if ($product->delete()) {
			craft()->market_variant->disableAllByProductId($product->id);

			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param int $productId
	 *
	 * @return Market_OptionTypeModel[]
	 */
	public function getOptionTypes($productId)
	{
		$product = Market_ProductRecord::model()->with('optionTypes')->findById($productId);

		return Market_OptionTypeModel::populateModels($product->optionTypes);
	}

	/**
	 * Set option types to a product
	 *
	 * @param int   $productId
	 * @param int[] $optionTypeIds
	 *
	 * @return bool
	 */
	public function setOptionTypes($productId, $optionTypeIds)
	{
		craft()->db->createCommand()->delete('market_product_optiontypes', ['productId' => $productId]);

		if ($optionTypeIds) {
			if (!is_array($optionTypeIds)) {
				$optionTypeIds = [$optionTypeIds];
			}

			$values = [];
			foreach ($optionTypeIds as $optionTypeId) {
				$values[] = [$optionTypeId, $productId];
			}

			craft()->db->createCommand()->insertAll('market_product_optiontypes', ['optionTypeId', 'productId'], $values);
		}
	}
}