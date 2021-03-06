<?php

/**
 * @file
 * Copyright (c) FormatData. All rights reserved.
 *
 * Distribution of RecipeML Processing Software in source and/or binary forms is
 * permitted provided that the following conditions are met:
 * - Distributions in source code must retain the above copyright notice and
 *   this list of conditions.
 * - Distributions in binary form must reproduce the above copyright notice and
 *   this list of conditions in the documentation and/or other materials
 *   provided with the distribution.
 * - All advertising materials and documentation for RecipeML Processing
 *   Software must display the following acknowledgment:
 *   "This product is RecipeML compatible."
 * - Names associated with RecipeML or FormatData must not be used to endorse or
 *   promote RecipeML Processing Software without prior written permission from
 *   FormatData. For written permission, please contact RecipeML@formatdata.com.
 */

namespace Drupal\migrate_recipeml\Plugin\migrate\source;

/**
 * A source that reads RecipeML markup.
 *
 * @MigrateSource(
 *   id = "recipeml"
 * )
 */
class RecipeML extends RecipeMLBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'id' => $this->t('A unique identifier for the recipe'),
      'title' => $this->t("The recipe's title"),
      'source' => $this->t("The recipe's source"),
      'yield_qty' => $this->t("The recipe's yield"),
      'yield_unit' => $this->t("The units of the recipe's yield"),
      'description' => $this->t('A description of the recipe'),
      'ingredients' => $this->t('The ingredients used in the recipe, with the following subkeys: qty, unit, item, prep'),
      'directions' => $this->t('Instructions for how to prepare the recipe'),
      'notes' => $this->t('Additional notes about the recipe'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function parseRecipeMl() {
    $data = [];

    // Check all of the nodes in the RecipeML file.  Look for <recipe> elements.
    while ($this->reader->read()) {
      if ($this->reader->nodeType == \XMLReader::ELEMENT && $this->reader->localName == 'recipe') {
        $recipe_data = [];
        $recipe = $this->getSimpleXml();
        if ($recipe !== FALSE && !is_null($recipe)) {
          // Extract data from the recipe's subelements.
          $recipe_data['id'] = $this->getValuesByXpath($recipe, '@id');
          $recipe_data['title'] = $this->getValuesByXpath($recipe, 'head/title');
          // Copy the title to the id field if no id was found.
          if ($recipe_data['id'] === []) {
            $recipe_data['id'] = $recipe_data['title'];
          }
          $recipe_data['source'] = $this->getValuesByXpath($recipe, 'head/source');
          $recipe_data['yield_qty'] = $this->getValuesByXpath($recipe, 'head/yield/qty');
          $recipe_data['yield_unit'] = $this->getValuesByXpath($recipe, 'head/yield/unit');
          $recipe_data['description'] = $this->getValuesByXpath($recipe, 'description');
          $recipe_data['ingredients'] = $this->getIngredients($recipe);
          $recipe_data['directions'] = $this->getValuesByXpath($recipe, 'directions');
          $recipe_data['note'] = $this->getValuesByXpath($recipe, 'note');
        }
        $data[] = $recipe_data;
      }
    }

    return $data;
  }

  /**
   * Parses a recipe's ingredients element into a data array.
   *
   * @param \SimpleXMLElement $element
   *   An XML element that should contain an ingredients/ing XPath.
   *
   * @return array
   *   The ingredient data.
   */
  protected function getIngredients(\SimpleXMLElement $element) {
    $ingredients = [];
    foreach ($element->xpath('ingredients/ing') as $ing) {
      $ingredient['qty'] = (string) $ing->amt->qty;
      $ingredient['unit'] = (string) $ing->amt->unit;
      $ingredient['item'] = (string) $ing->item;
      $ingredient['prep'] = (string) $ing->prep;
      $ingredients[] = $ingredient;
    }
    return $ingredients;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return ['id' => ['type' => 'string']];
  }

}
