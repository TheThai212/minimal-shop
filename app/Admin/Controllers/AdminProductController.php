<?php
namespace App\Admin\Controllers;

use SCart\Core\Front\Models\ShopAttributeGroup;
use SCart\Core\Front\Models\ShopBrand;
use SCart\Core\Front\Models\ShopTax;
use SCart\Core\Front\Models\ShopLanguage;
use SCart\Core\Front\Models\ShopWeight;
use SCart\Core\Front\Models\ShopLength;
use SCart\Core\Front\Models\ShopProductAttribute;
use SCart\Core\Front\Models\ShopProductBuild;
use SCart\Core\Front\Models\ShopProductGroup;
use SCart\Core\Front\Models\ShopProductImage;
use SCart\Core\Front\Models\ShopSupplier;
use SCart\Core\Front\Models\ShopProductDownload;
use SCart\Core\Front\Models\ShopProductProperty;
use SCart\Core\Front\Models\ShopCustomField;
use SCart\Core\Front\Models\ShopCustomFieldDetail;
use SCart\Core\Admin\Models\AdminProduct;
use SCart\Core\Admin\Models\AdminCategory;
use Illuminate\Support\Facades\Validator;

class AdminProductController extends \SCart\Core\Admin\Controllers\AdminProductController
{

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * Post create new item in admin
     * @return [type] [description]
     */

    public function postCreate()
    {
        
        $data = request()->all();
        $langFirst = array_key_first(sc_language_all()->toArray()); //get first code language active
        $data['alias'] = !empty($data['alias'])?$data['alias']:$data['descriptions'][$langFirst]['name'];
        $data['alias'] = sc_word_format_url($data['alias']);
        $data['alias'] = sc_word_limit($data['alias'], 100);

        switch ($data['kind']) {
            case SC_PRODUCT_SINGLE: // product single
                $arrValidation = [
                    'kind'                       => 'required',
                    'sort'                       => 'numeric|min:0',
                    'minimum'                    => 'numeric|min:0',
                    'descriptions.*.name'        => 'required|string|max:100',
                    'descriptions.*.keyword'     => 'nullable|string|max:100',
                    'descriptions.*.description' => 'nullable|string|max:100',
                    'descriptions.*.content'     => 'required|string',
                    'category'                   => 'required',
                    'sku'                        => 'required|regex:/(^([0-9A-Za-z\-_]+)$)/|product_sku_unique',
                    'alias'                      => 'required|regex:/(^([0-9A-Za-z\-_]+)$)/|string|max:120|product_alias_unique',
                ];

                //Custom fields
                $customFields = (new ShopCustomField)->getCustomField($type = 'product');
                if ($customFields) {
                    foreach ($customFields as $field) {
                        if ($field->required) {
                            $arrValidation['fields.'.$field->code] = 'required';
                        }
                    }
                }

                $arrValidation = $this->validateAttribute($arrValidation);
                
                $arrMsg = [
                    'descriptions.*.name.required'    => sc_language_render('validation.required', ['attribute' => sc_language_render('product.name')]),
                    'descriptions.*.content.required' => sc_language_render('validation.required', ['attribute' => sc_language_render('product.content')]),
                    'category.required'               => sc_language_render('validation.required', ['attribute' => sc_language_render('product.category')]),
                    'sku.regex'                       => sc_language_render('product.sku_validate'),
                    'sku.product_sku_unique'          => sc_language_render('product.sku_unique'),
                    'alias.regex'                     => sc_language_render('product.alias_validate'),
                    'alias.product_alias_unique'      => sc_language_render('product.alias_unique'),
                ];
                break;

            case SC_PRODUCT_BUILD: //product build
                $arrValidation = [
                    'kind'                       => 'required',
                    'sort'                       => 'numeric|min:0',
                    'minimum'                    => 'numeric|min:0',
                    'descriptions.*.name'        => 'required|string|max:100',
                    'descriptions.*.keyword'     => 'nullable|string|max:100',
                    'descriptions.*.description' => 'nullable|string|max:100',
                    'category'                   => 'required',
                    'sku'                        => 'required|regex:/(^([0-9A-Za-z\-_]+)$)/|product_sku_unique',
                    'alias'                      => 'required|regex:/(^([0-9A-Za-z\-_]+)$)/|string|max:120|product_alias_unique',
                    'productBuild'               => 'required',
                    'productBuildQty'            => 'required',
                ];

                $arrValidation = $this->validateAttribute($arrValidation);

                $arrMsg = [
                    'descriptions.*.name.required' => sc_language_render('validation.required', ['attribute' => sc_language_render('product.name')]),
                    'category.required'            => sc_language_render('validation.required', ['attribute' => sc_language_render('product.category')]),
                    'sku.regex'                    => sc_language_render('product.sku_validate'),
                    'sku.product_sku_unique'       => sc_language_render('product.sku_unique'),
                    'alias.regex'                  => sc_language_render('product.alias_validate'),
                    'alias.product_alias_unique'   => sc_language_render('product.alias_unique'),
                ];
                break;

            case SC_PRODUCT_GROUP: //product group
                $arrValidation = [
                    'kind'                       => 'required',
                    'productInGroup'             => 'required',
                    'sku'                        => 'required|regex:/(^([0-9A-Za-z\-_]+)$)/|product_sku_unique',
                    'alias'                      => 'required|regex:/(^([0-9A-Za-z\-_]+)$)/|string|max:120|product_alias_unique',
                    'sort'                       => 'numeric|min:0',
                    'category'                   => 'required',
                    'descriptions.*.name'        => 'required|string|max:200',
                    'descriptions.*.keyword'     => 'nullable|string|max:200',
                    'descriptions.*.description' => 'nullable|string|max:300',
                ];
                $arrMsg = [
                    'descriptions.*.name.required' => sc_language_render('validation.required', ['attribute' => sc_language_render('product.name')]),
                    'sku.regex'                    => sc_language_render('product.sku_validate'),
                    'category.required'            => sc_language_render('validation.required', ['attribute' => sc_language_render('product.category')]),
                    'sku.product_sku_unique'       => sc_language_render('product.sku_unique'),
                    'alias.regex'                  => sc_language_render('product.alias_validate'),
                    'alias.product_alias_unique'   => sc_language_render('product.alias_unique'),
                ];
                break;

            default:
                $arrValidation = [
                    'kind' => 'required',
                ];
                break;
        }

        $validator = Validator::make($data, $arrValidation, $arrMsg ?? []);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($data);
        }

        $category        = $data['category'] ?? [];
        $attribute       = $data['attribute'] ?? [];
        $descriptions    = $data['descriptions'];
        $productInGroup  = $data['productInGroup'] ?? [];
        $productBuild    = $data['productBuild'] ?? [];
        $productBuildQty = $data['productBuildQty'] ?? [];
        $subImages       = $data['sub_image'] ?? [];
        $downloadPath    = $data['download_path'] ?? '';
        $dataInsert = [
            'brand_id'       => $data['brand_id'] ?? 0,
            'supplier_id'    => $data['supplier_id'] ?? 0,
            'category_store_id' => $data['category_store_id'] ?? 0,
            'price'          => $data['price'] ?? 0,
            'quantity'          => $data['quantity'] ?? 0,
            'sku'            => $data['sku'],
            'cost'           => $data['cost'] ?? 0,
            'stock'          => $data['stock'] ?? 0,
            'weight_class'   => $data['weight_class'] ?? '',
            'length_class'   => $data['length_class'] ?? '',
            'weight'         => $data['weight'] ?? 0,
            'height'         => $data['height'] ?? 0,
            'length'         => $data['length'] ?? 0,
            'width'          => $data['width'] ?? 0,
            'kind'           => $data['kind'] ?? SC_PRODUCT_SINGLE,
            'alias'          => $data['alias'],
            'property'       => $data['property'] ?? SC_PROPERTY_PHYSICAL,
            'image'          => $data['image'] ?? '',
            'tax_id'         => $data['tax_id'] ?? 0,
            'status'         => (!empty($data['status']) ? 1 : 0),
            'sort'           => (int) $data['sort'],
            'minimum'        => (int) ($data['minimum'] ?? 0),
            'store_id'       => session('adminStoreId'),
        ];

        if(!empty($data['date_available'])) {
            $dataInsert['date_available'] = $data['date_available'];
        }
        //insert product
        $product = AdminProduct::createProductAdmin($dataInsert);

        //Promoton price
        if (isset($data['price_promotion']) && in_array($data['kind'], [SC_PRODUCT_SINGLE, SC_PRODUCT_BUILD])) {
            $arrPromotion['price_promotion'] = $data['price_promotion'];
            $arrPromotion['date_start'] = $data['price_promotion_start'] ? $data['price_promotion_start'] : null;
            $arrPromotion['date_end'] = $data['price_promotion_end'] ? $data['price_promotion_end'] : null;
            $product->promotionPrice()->create($arrPromotion);
        }

        //Insert category
        if ($category) {
            $product->categories()->attach($category);
        }

        //Insert group
        if ($productInGroup && $data['kind'] == SC_PRODUCT_GROUP) {
            $arrDataGroup = [];
            foreach ($productInGroup as $pID) {
                if ((int) $pID) {
                    $arrDataGroup[$pID] = new ShopProductGroup(['product_id' => $pID]);
                }
            }
            $product->groups()->saveMany($arrDataGroup);
        }

        //Insert Build
        if ($productBuild && $data['kind'] == SC_PRODUCT_BUILD) {
            $arrDataBuild = [];
            foreach ($productBuild as $key => $pID) {
                if ((int) $pID) {
                    $arrDataBuild[$pID] = new ShopProductBuild(['product_id' => $pID, 'quantity' => $productBuildQty[$key]]);
                }
            }
            $product->builds()->saveMany($arrDataBuild);
        }

        //Insert attribute
        if ($attribute && $data['kind'] == SC_PRODUCT_SINGLE) {
            $arrDataAtt = [];
            foreach ($attribute as $group => $rowGroup) {
                if (count($rowGroup)) {
                    foreach ($rowGroup['name'] as $key => $nameAtt) {
                        if ($nameAtt) {
                            $arrDataAtt[] = new ShopProductAttribute(['name' => $nameAtt, 'add_price' => $rowGroup['add_price'][$key],  'attribute_group_id' => $group]);
                        }
                    }
                }

            }
            $product->attributes()->saveMany($arrDataAtt);
        }

        //Insert path download
        if (!empty($data['property']) && $data['property'] == SC_PROPERTY_DOWNLOAD && $downloadPath) {
            (new ShopProductDownload)->insert(['product_id' => $product->id, 'path' => $downloadPath]);
        }

        //Insert custom fields
        if (!empty($data['fields'])) {
            $dataField = [];
            foreach ($data['fields'] as $key => $value) {
                $field = (new ShopCustomField)->where('code', $key)->where('type', 'product')->first();
                if ($field) {
                    $dataField[] = [
                        'custom_field_id' => $field->id,
                        'rel_id' => $product->id,
                        'text' => trim($value),
                    ];
                }
            }
            if ($dataField) {
                (new ShopCustomFieldDetail)->insert($dataField);
            }
        }


        //Insert description
        $dataDes = [];
        $languages = $this->languages;
        foreach ($languages as $code => $value) {
            $dataDes[] = [
                'product_id'  => $product->id,
                'lang'        => $code,
                'name'        => $descriptions[$code]['name'],
                'keyword'     => $descriptions[$code]['keyword'],
                'description' => $descriptions[$code]['description'],
                'content'     => $descriptions[$code]['content'] ?? '',
            ];
        }

        AdminProduct::insertDescriptionAdmin($dataDes);

        //Insert sub mages
        if ($subImages && in_array($data['kind'], [SC_PRODUCT_SINGLE, SC_PRODUCT_BUILD])) {
            $arrSubImages = [];
            foreach ($subImages as $key => $image) {
                if ($image) {
                    $arrSubImages[] = new ShopProductImage(['image' => $image]);
                }
            }
            $product->images()->saveMany($arrSubImages);
        }

        sc_clear_cache('cache_product');

        return redirect()->route('admin_product.index')->with('success', sc_language_render('product.admin.create_success'));

    }


    /**
     * Form create new item in admin
     * @return [type] [description]
     */
    public function create()
    {
        // html add more images
        $htmlMoreImage = '<div class="input-group"><input type="text" id="id_sub_image" name="sub_image[]" value="image_value" class="form-control rounded-0 input-sm sub_image" placeholder=""  /><span class="input-group-btn"><a data-input="id_sub_image" data-preview="preview_sub_image" data-type="product" class="btn btn-primary lfm"><i class="fa fa-picture-o"></i> Choose</a></span></div><div id="preview_sub_image" class="img_holder"></div>';
        //end add more images

        // html select attribute
        $htmlProductAtrribute = '<tr><td><br><input type="text" name="attribute[attribute_group][name][]" value="attribute_value" class="form-control rounded-0 input-sm" placeholder="' . sc_language_render('product.admin.add_attribute_place') . '" /></td><td><br><input type="number" name="attribute[attribute_group][add_price][]" value="add_price_value" class="form-control rounded-0 input-sm" placeholder="' . sc_language_render('product.admin.add_price_place') . '"></td><td><br><span title="Remove" class="btn btn-flat btn-danger removeAttribute"><i class="fa fa-times"></i></span></td></tr>';
        //end select attribute

        $data = [
            'title'                => sc_language_render('product.admin.add_new_title'),
            'subTitle'             => '',
            'title_description'    => sc_language_render('product.admin.add_new_des'),
            'icon'                 => 'fa fa-plus',
            'languages'            => $this->languages,
            'categories'           => $this->categories,
            'brands'               => (new ShopBrand)->getListAll(),
            'suppliers'            => (new ShopSupplier)->getListAll(),
            'taxs'                 => (new ShopTax)->getListAll(),
            'properties'            => $this->properties,
            'kinds'                => $this->kinds,
            'attributeGroup'       => $this->attributeGroup,
            'htmlMoreImage'        => $htmlMoreImage,
            'htmlProductAtrribute' => $htmlProductAtrribute,
            'listWeight'           => $this->listWeight,
            'listLength'           => $this->listLength,
            'customFields'         => (new ShopCustomField)->getCustomField($type = 'product'),
        ];

        return view('screen.product_add')
            ->with($data);
    }


    /*
    * update status
    */
    public function postEdit($id)
    {
        $product = (new AdminProduct)->getProductAdmin($id);
        if ($product === null) {
            return redirect()->route('admin.data_not_found')->with(['url' => url()->full()]);
        }
        $data = request()->all();
        $langFirst = array_key_first(sc_language_all()->toArray()); //get first code language active
        $data['alias'] = !empty($data['alias'])?$data['alias']:$data['descriptions'][$langFirst]['name'];
        $data['alias'] = sc_word_format_url($data['alias']);
        $data['alias'] = sc_word_limit($data['alias'], 100);

        switch ($product['kind']) {
            case SC_PRODUCT_SINGLE: // product single
                $arrValidation = [
                    'sort' => 'numeric|min:0',
                    'minimum' => 'numeric|min:0',
                    'descriptions.*.name' => 'required|string|max:200',
                    'descriptions.*.keyword' => 'nullable|string|max:200',
                    'descriptions.*.description' => 'nullable|string|max:300',
                    'descriptions.*.content' => 'required|string',
                    'category' => 'required',
                    'sku' => 'required|regex:/(^([0-9A-Za-z\-_]+)$)/|product_sku_unique:'.$id,
                    'alias' => 'required|regex:/(^([0-9A-Za-z\-_]+)$)/|string|max:120|product_alias_unique:'.$id,
                ];

                //Custom fields
                $customFields = (new ShopCustomField)->getCustomField($type = 'product');
                if ($customFields) {
                    foreach ($customFields as $field) {
                        if ($field->required) {
                            $arrValidation['fields.'.$field->code] = 'required';
                        }
                    }
                }

                $arrValidation = $this->validateAttribute($arrValidation);

                $arrMsg = [
                    'descriptions.*.name.required'    => sc_language_render('validation.required', ['attribute' => sc_language_render('product.name')]),
                    'descriptions.*.content.required' => sc_language_render('validation.required', ['attribute' => sc_language_render('product.content')]),
                    'category.required'               => sc_language_render('validation.required', ['attribute' => sc_language_render('product.category')]),
                    'sku.regex'                       => sc_language_render('product.sku_validate'),
                    'sku.product_sku_unique'          => sc_language_render('product.sku_unique'),
                    'alias.regex'                     => sc_language_render('product.alias_validate'),
                    'alias.product_alias_unique'      => sc_language_render('product.alias_unique'),
                ];
                break;
            case SC_PRODUCT_BUILD: //product build
                $arrValidation = [
                    'sort' => 'numeric|min:0',
                    'minimum' => 'numeric|min:0',
                    'descriptions.*.name' => 'required|string|max:200',
                    'descriptions.*.keyword' => 'nullable|string|max:200',
                    'descriptions.*.description' => 'nullable|string|max:300',
                    'category' => 'required',
                    'sku' => 'required|regex:/(^([0-9A-Za-z\-_]+)$)/|product_sku_unique:'.$id,
                    'alias' => 'required|regex:/(^([0-9A-Za-z\-_]+)$)/|string|max:120|product_alias_unique:'.$id,
                    'productBuild' => 'required',
                    'productBuildQty' => 'required',
                ];

                $arrValidation = $this->validateAttribute($arrValidation);
                
                $arrMsg = [
                    'descriptions.*.name.required' => sc_language_render('validation.required', ['attribute' => sc_language_render('product.name')]),
                    'category.required'            => sc_language_render('validation.required', ['attribute' => sc_language_render('product.category')]),
                    'sku.regex'                    => sc_language_render('product.sku_validate'),
                    'sku.product_sku_unique'       => sc_language_render('product.sku_unique'),
                    'alias.regex'                  => sc_language_render('product.alias_validate'),
                    'alias.product_alias_unique'   => sc_language_render('product.alias_unique'),
                ];
                break;

            case SC_PRODUCT_GROUP: //product group
                $arrValidation = [
                    'sku' => 'required|regex:/(^([0-9A-Za-z\-_]+)$)/|product_sku_unique:'.$id,
                    'alias' => 'required|regex:/(^([0-9A-Za-z\-_]+)$)/|string|max:120|product_alias_unique:'.$id,
                    'productInGroup' => 'required',
                    'category' => 'required',
                    'sort' => 'numeric|min:0',
                    'descriptions.*.name' => 'required|string|max:200',
                    'descriptions.*.keyword' => 'nullable|string|max:200',
                    'descriptions.*.description' => 'nullable|string|max:300',
                ];
                $arrMsg = [
                    'sku.regex'                    => sc_language_render('product.sku_validate'),
                    'sku.product_sku_unique'       => sc_language_render('product.sku_unique'),
                    'category.required'            => sc_language_render('validation.required', ['attribute' => sc_language_render('product.category')]),
                    'alias.regex'                  => sc_language_render('product.alias_validate'),
                    'alias.product_alias_unique'   => sc_language_render('product.alias_unique'),
                    'descriptions.*.name.required' => sc_language_render('validation.required', ['attribute' => sc_language_render('product.name')]),
                ];
                break;

            default:
                break;
        }

        $validator = Validator::make($data, $arrValidation, $arrMsg ?? []);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($data);
        }
        //Edit

        $category        = $data['category'] ?? [];
        $attribute       = $data['attribute'] ?? [];
        $productInGroup  = $data['productInGroup'] ?? [];
        $productBuild    = $data['productBuild'] ?? [];
        $productBuildQty = $data['productBuildQty'] ?? [];
        $subImages       = $data['sub_image'] ?? [];
        $downloadPath    = $data['download_path'] ?? '';
        $dataUpdate = [
            'image'        => $data['image'] ?? '',
            'tax_id'       => $data['tax_id'] ?? 0,
            'brand_id'     => $data['brand_id'] ?? 0,
            'supplier_id'  => $data['supplier_id'] ?? 0,
            'category_store_id'     => $data['category_store_id'] ?? 0,
            'price'        => $data['price'] ?? 0,
            'quantity'        => $data['quantity'] ?? 0,
            'cost'         => $data['cost'] ?? 0,
            'stock'        => $data['stock'] ?? 0,
            'weight_class' => $data['weight_class'] ?? '',
            'length_class' => $data['length_class'] ?? '',
            'weight'       => $data['weight'] ?? 0,
            'height'       => $data['height'] ?? 0,
            'length'       => $data['length'] ?? 0,
            'width'        => $data['width'] ?? 0,
            'property'     => $data['property'] ?? SC_PROPERTY_PHYSICAL,
            'sku'          => $data['sku'],
            'alias'        => $data['alias'],
            'status'       => (!empty($data['status']) ? 1 : 0),
            'sort'         => (int) $data['sort'],
            'minimum'      => (int) ($data['minimum'] ?? 0),
            'store_id'     => session('adminStoreId'),
        ];
        if (!empty($data['date_available'])) {
            $dataUpdate['date_available'] = $data['date_available'];
        }
        $product->update($dataUpdate);


        //Update custom field
        if (!empty($data['fields'])) {
            (new ShopCustomFieldDetail)
                ->join(SC_DB_PREFIX.'shop_custom_field', SC_DB_PREFIX.'shop_custom_field.id', SC_DB_PREFIX.'shop_custom_field_detail.custom_field_id')
                ->select('code', 'name', 'text')
                ->where(SC_DB_PREFIX.'shop_custom_field_detail.rel_id', $product->id)
                ->where(SC_DB_PREFIX.'shop_custom_field.type', 'product')
                ->delete();

            $dataField = [];
            foreach ($data['fields'] as $key => $value) {
                $field = (new ShopCustomField)->where('code', $key)->where('type', 'product')->first();
                if ($field) {
                    $dataField[] = [
                        'custom_field_id' => $field->id,
                        'rel_id' => $product->id,
                        'text' => trim($value),
                    ];
                }
            }
            if ($dataField) {
                (new ShopCustomFieldDetail)->insert($dataField);
            }
        }



        //Promoton price
        $product->promotionPrice()->delete();
        if (isset($data['price_promotion']) && in_array($product['kind'], [SC_PRODUCT_SINGLE, SC_PRODUCT_BUILD])) {
            $arrPromotion['price_promotion'] = $data['price_promotion'];
            $arrPromotion['date_start'] = $data['price_promotion_start'] ? $data['price_promotion_start'] : null;
            $arrPromotion['date_end'] = $data['price_promotion_end'] ? $data['price_promotion_end'] : null;
            $product->promotionPrice()->create($arrPromotion);
        }

        $product->descriptions()->delete();
        $dataDes = [];
        foreach ($data['descriptions'] as $code => $row) {
            $dataDes[] = [
                'product_id' => $id,
                'lang' => $code,
                'name' => $row['name'],
                'keyword' => $row['keyword'],
                'description' => $row['description'],
                'content' => $row['content'] ?? '',
            ];
        }
        AdminProduct::insertDescriptionAdmin($dataDes);

        $product->categories()->detach();
        if (count($category)) {
            $product->categories()->attach($category);
        }

        //Update group
        if ($product['kind'] == SC_PRODUCT_GROUP) {
            $product->groups()->delete();
            if (count($productInGroup)) {
                $arrDataGroup = [];
                foreach ($productInGroup as $pID) {
                    if ((int) $pID) {
                        $arrDataGroup[$pID] = new ShopProductGroup(['product_id' => $pID]);
                    }
                }
                $product->groups()->saveMany($arrDataGroup);
            }

        }

        //Update Build
        if ($product['kind'] == SC_PRODUCT_BUILD) {
            $product->builds()->delete();
            if (count($productBuild)) {
                $arrDataBuild = [];
                foreach ($productBuild as $key => $pID) {
                    if ((int) $pID) {
                        $arrDataBuild[$pID] = new ShopProductBuild(['product_id' => $pID, 'quantity' => $productBuildQty[$key]]);
                    }
                }
                $product->builds()->saveMany($arrDataBuild);
            }

        }

        //Update path download
        (new ShopProductDownload)->where('product_id', $product->id)->delete();
        if ($product['property'] == SC_PROPERTY_DOWNLOAD && $downloadPath) {
            (new ShopProductDownload)->insert(['product_id' => $product->id, 'path' => $downloadPath]);
        }


        //Update attribute
        if ($product['kind'] == SC_PRODUCT_SINGLE) {
            $product->attributes()->delete();
            if (count($attribute)) {
                $arrDataAtt = [];
                foreach ($attribute as $group => $rowGroup) {
                    if (count($rowGroup)) {
                        foreach ($rowGroup['name'] as $key => $nameAtt) {
                            if ($nameAtt) {
                                $arrDataAtt[] = new ShopProductAttribute(['name' => $nameAtt, 'add_price' => $rowGroup['add_price'][$key], 'attribute_group_id' => $group]);
                            }
                        }
                    }

                }
                $product->attributes()->saveMany($arrDataAtt);
            }

        }

        //Update sub mages
        if ($subImages && in_array($product['kind'], [SC_PRODUCT_SINGLE, SC_PRODUCT_BUILD])) {
            $product->images()->delete();
            $arrSubImages = [];
            foreach ($subImages as $key => $image) {
                if ($image) {
                    $arrSubImages[] = new ShopProductImage(['image' => $image]);
                }
            }
            $product->images()->saveMany($arrSubImages);
        }

        sc_clear_cache('cache_product');

        return redirect()->route('admin_product.index')->with('success', sc_language_render('product.admin.edit_success'));

    }

    /*
    * Form edit
    */
    public function edit($id)
    {
        $product = (new AdminProduct)->getProductAdmin($id);

        if ($product === null) {
            return redirect()->route('admin.data_not_found')->with(['url' => url()->full()]);
        }

        $listProductSingle = (new AdminProduct)->getProductSelectAdmin(['kind' => [SC_PRODUCT_SINGLE]]);

        // html select product group
        $htmlSelectGroup = '<div class="select-product">';
        $htmlSelectGroup .= '<table width="100%"><tr><td width="80%"><select class="form-control rounded-0 productInGroup select2" data-placeholder="' . sc_language_render('product.admin.select_product_in_group') . '" style="width: 100%;" name="productInGroup[]" >';
        $htmlSelectGroup .= '';
        foreach ($listProductSingle as $k => $v) {
            $htmlSelectGroup .= '<option value="' . $k . '">' . $v['name'] . '</option>';
        }
        $htmlSelectGroup .= '</select></td><td><span title="Remove" class="btn btn-flat btn-danger removeproductInGroup"><i class="fa fa-times"></i></span></td></tr></table>';
        $htmlSelectGroup .= '</div>';
        //End select product group

        // html select product build
        $htmlSelectBuild = '<div class="select-product">';
        $htmlSelectBuild .= '<table width="100%"><tr><td width="70%"><select class="form-control rounded-0 productInGroup select2" data-placeholder="' . sc_language_render('product.admin.select_product_in_build') . '" style="width: 100%;" name="productBuild[]" >';
        $htmlSelectBuild .= '';
        foreach ($listProductSingle as $k => $v) {
            $htmlSelectBuild .= '<option value="' . $k . '">' . $v['name'] . '</option>';
        }
        $htmlSelectBuild .= '</select></td><td style="width:100px"><input class="form-control rounded-0"  type="number" name="productBuildQty[]" value="1" min=1></td><td><span title="Remove" class="btn btn-flat btn-danger removeproductBuild"><i class="fa fa-times"></i></span></td></tr></table>';
        $htmlSelectBuild .= '</div>';
        //end select product build

        // html select attribute
        $htmlProductAtrribute = '<tr><td><br><input type="text" name="attribute[attribute_group][name][]" value="attribute_value" class="form-control rounded-0 input-sm" placeholder="' . sc_language_render('product.admin.add_attribute_place') . '" /></td><td><br><input type="number" name="attribute[attribute_group][add_price][]" value="add_price_value" class="form-control rounded-0 input-sm" placeholder="' . sc_language_render('product.admin.add_price_place') . '"></td><td><br><span title="Remove" class="btn btn-flat btn-danger removeAttribute"><i class="fa fa-times"></i></span></td></tr>';
        //end select attribute


        $data = [
            'title'                => sc_language_render('product.admin.edit'),
            'subTitle'             => '',
            'title_description'    => '',
            'icon'                 => 'fa fa-edit',
            'languages'            => $this->languages,
            'product'              => $product,
            'categories'           => $this->categories,
            'brands'               => (new ShopBrand)->getListAll(),
            'suppliers'            => (new ShopSupplier)->getListAll(),
            'taxs'                 => (new ShopTax)->getListAll(),
            'properties'            => $this->properties,
            'kinds'                => $this->kinds,
            'attributeGroup'       => $this->attributeGroup,
            'htmlSelectGroup'      => $htmlSelectGroup,
            'htmlSelectBuild'      => $htmlSelectBuild,
            'listProductSingle'    => $listProductSingle,
            'htmlProductAtrribute' => $htmlProductAtrribute,
            'listWeight'           => $this->listWeight,
            'listLength'           => $this->listLength,

        ];

        //Only prduct single have custom field
        if ($product->kind == SC_PRODUCT_SINGLE) {
            $data['customFields'] = (new ShopCustomField)->getCustomField($type = 'product');
        } else {
            $data['customFields'] = [];
        }
        return view('screen.product_edit')
            ->with($data);
    }
}
