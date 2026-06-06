<?php

namespace Digidennis\FabricColors\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Digidennis\FabricColors\Service\OptionTypeLookup;
use Digidennis\FabricColors\Model\ColorFactory;
use Digidennis\FabricColors\Model\ResourceModel\Color as ColorResource;
use Digidennis\FabricColors\Model\ResourceModel\ColorImage as ColorImageResource;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions;

class FabricColors extends AbstractModifier
{
    private OptionTypeLookup $optionTypeLookup;
    private ColorFactory $colorFactory;
    private ColorResource $colorResource;
    private ColorImageResource $imageResource;
    private StoreManagerInterface $storeManager;
    protected $meta = [];

    public function __construct(
        OptionTypeLookup $optionTypeLookup,
        ColorFactory $colorFactory,
        ColorResource $colorResource,
        ColorImageResource $imageResource,
        StoreManagerInterface $storeManager
    ) {
        $this->optionTypeLookup = $optionTypeLookup;
        $this->colorFactory     = $colorFactory;
        $this->colorResource    = $colorResource;
        $this->imageResource    = $imageResource;
        $this->storeManager     = $storeManager;
    }

    public function modifyData(array $data): array
    {
        foreach ($data as $productId => &$productData) {
            if (empty($productData['product']['options'])) {
                continue;
            }

            foreach ($productData['product']['options'] as &$option) {
                if (empty($option['values'])) {
                    continue;
                }

                foreach ($option['values'] as &$value) {
                    $optionTypeId = isset($value['option_type_id'])
                        ? (int)$value['option_type_id']
                        : $this->optionTypeLookup->getOptionTypeId(
                            (int)$option['option_id'],
                            (int)$value['sort_order']
                        );

                    if (!$optionTypeId) {
                        continue;
                    }

                    $color = $this->colorFactory->create();
                    $this->colorResource->loadByOptionTypeIntoModel(
                        $color,
                        (int)$productId,
                        (int)$option['option_id'],
                        $optionTypeId
                    );

                    if (!$color->getId()) {
                        continue;
                    }

                    $images = $this->imageResource->getImagesByColorId((int)$color->getId());

                    if (!$images) {
                        continue;
                    }

                    $value['fabric_image'] = $this->convertImagesToUiFormat($images);
                }
            }
        }

        return $data;
    }

    public function modifyMeta(array $meta)
    {
        $this->meta = $meta;
        $this->addFields();
        return $this->meta;
    }

    protected function addFields()
    {
        $group = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
        $option = CustomOptions::CONTAINER_OPTION;

        // Tilføj feltet til value‑niveauet
        $this->meta[$group]['children']['options']['children']['record']['children']
        [$option]['children']['values']['children']['record']['children'] =
            array_replace_recursive(
                $this->meta[$group]['children']['options']['children']['record']['children']
                [$option]['children']['values']['children']['record']['children'],
                $this->getValueFieldsConfig()
            );
    }

    protected function getValueFieldsConfig()
    {
        return [
            'fabric_image' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => __('Fabric Image'),
                            'componentType' => \Magento\Ui\Component\Form\Field::NAME,
                            'formElement' => 'fileUploader',
                            'component' => 'Magento_Ui/js/form/element/file-uploader',
                            'dataType' => 'file',
                            'dataScope' => 'fabric_image',
                            'sortOrder' => 50,
                            'visible' => true,
                            'placeholderType' => 'image',
                            'allowDrop' => true,
                            'isMultipleFiles' => true,
                            'uploaderConfig' => [
                                'url' => 'digidennis_fabriccolors/image/upload'
                            ],
                        ],
                    ],
                ],
            ]
        ];
    }

    private function convertImagesToUiFormat(array $images): array
    {
        $mediaUrl = $this->storeManager
            ->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

        $result = [];

        foreach ($images as $img) {
            $path = ltrim($img['image'], '/');

            $result[] = [
                'name' => basename($path),
                'url'  => $mediaUrl . $path,
                'file' => '/' . $path,
                'size' => 0,
                'type' => 'image/jpeg',
            ];
        }

        return $result;
    }
}
