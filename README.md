Opencart WEB API
================

This is a small module that adds the ability to remotely work with the OpenCart 1.5.3+ via the REST API.

 * Homepage: http://App-z.net
 * Android App Builder: http://app-z.net/app_builder 
 * Original Sources: https://github.com/ethernet1/opencart-webapi
 * Ddocumentation in Russian: http://zenwalker.ru/lab/opencart-webapi/documentation.html


Build Android Client Application
--------
<br>
<a href="http://app-z.net/app_builder/"><img src="http://app-z.net/web/images/opencart514x200.png" /></a>
<br>
* Go to <a href="http://app-z.net/app_builder/">http://app-z.net/app_builder/</a><br>
* Register and login<br>
* Use Actions:<br>
Edit application <br>
Delete an application <br>
Build the application (Usually 45..90 sec)<br>
Download the Android application (if all gone success. If happen error try repeat in 15 min)<br>
<br>
ic_launcher - Application icon. Recommended size 144x144<br>
List Icon - Icon in list from the left in front of each paragraph. Should be square small image<br>
Image Top - The image in the header of screen smartphone<br>
Image Bottom - The image in the footer of screen smartphone<br>
<br>
Package Name - Should be unique and consist of at least two segments (Example: com.mysite)<br>
Base URL - Your shop url. Check it please. Should be begin with http:// or https:// prefix<br>
<br>
Notice!<br>
Key parameter in OpenCart admin panel should be as "key1" <br>
<br>
Magento REST-API see on github<br>
<a href="https://github.com/app-z/magento-android-web-api">magento-android-web-api</a>
<br>


Features:

 * [Get categories list](#get-categories)
 * [Get category info](#get-category-info)
 * [Get products from category](#get-products)
 * [Get full info from product](#get-product)


Examples
--------

### Get categories

Request:

  http://example.com/?route=feed/web_api/categories&parent=0&level=2


Params:

 * $_GET['parent']: parent category id
 * $_GET['level']:  depth level


Answer:

	{
	  "success": true,
	  
	  categories:
	  [
	    {
	      "category_id": "1",
	      "name": "First category",
	      "parent_id": "0",
	      "href": "http://example.com/index.php?route=product/category&category_id=1"
	      "categories": null,
	    },
	    {
	      "category_id": "2",
	      "name": "Second category",
	      "parent_id": "0",
	      "href": "http://example.com/index.php?route=product/category&category_id=2"
	      "categories": [
	        {
	          "category_id": "3",
	          "name": "Inner category",
	          "parent_id": "2",
	          "href": "http://example.com/index.php?route=product/category&category_id=3"
	          "categories": null
	        },
	        {
	          "category_id": "3",
	          "name": "Inner category",
	          "parent_id": "2",
	          "href": "http://example.com/index.php?route=product/category&category_id=4"
	          "categories": null
	        }
	      ]
	    }
	  ]
	}


### Get category info

Request:

  http://example.com/?route=feed/web_api/category&id=1


Params:

 * $_GET['id']: category id


Answer:

  {
    "success":true,

    "category":{
      "id": "1",
      "name": "Category name",
      "description": "",
      "href": "http://example.com/index.php?route=product/category&category_id=1"
    }
  }



### Get products

Request:

  http://example.com/?route=feed/web_api/products&category=1


Params:

 * $_GET['category']: parent category id


Answer:

	{
	  "success": true,
	  
	  products: [
	    {
	      "id": "6",
	      "name": "Product name",
	      "description": "",
	      "pirce": "$455.00",
	      "href": "http://example.com/index.php?route=product/product&product_id=6",
	      "thumb": false,
	      "special": false,
	      "rating": 0
	    },
	    {
	      "id": "7",
	      "name": "Product name",
	      "description": "",
	      "pirce": "$25.00",
	      "href": "http://example.com/index.php?route=product/product&product_id=7",
	      "thumb": false,
	      "special": false,
	      "rating": 0
	    }
	  ]
	}



### Get product

Request:

  http://example.com/?route=feed/web_api/product&id=1

Params:

 * $_GET['id']: product id

Answer:

	{
	  "success":true,

	  "product":{
	    "id": "2",
	    "seo_h1": "",
	    "name": "Product name",
	    "manufacturer":null,
	    "model": "A1",
	    "reward":null,
	    "points": "0",
	    "image": "",
	    "images":[],
	    "price": "$10.00",
	    "special":false,
	    "discounts":[],
	    "options":[
	      {
	        "product_option_id": "2",
	        "option_id": "5",
	        "name": "Select",
	        "type": "select",
	        "option_value":[

	        ],
	        "required": "1"
	      },
	      {
	        "product_option_id": "1",
	        "option_id": "2",
	        "name": "Checkbox",
	        "type": "checkbox",
	        "option_value":[
	          {
	            "product_option_value_id": "1",
	            "option_value_id": "24",
	            "name": "Checkbox 2",
	            "image":null,
	            "price": "$20.00",
	            "price_prefix": "+"
	          }
	        ],
	        "required": "1"
	      }
	    ],
	    "minimum": "1",
	    "rating": 0,
	    "description": "HTML Description",
	    "attribute_groups":[
	      {
	        "attribute_group_id": "1",
	        "name": "Attribute group 1",
	        "attribute":[
	          {
	            "attribute_id": "10",
	            "name": "Attribute 1",
	            "text": "20"
	          },
	          {
	            "attribute_id": "70",
	            "name": "Attrubute 2",
	            "text": "Value"
	          }
	        ]
	      }
	    ]
	  }
	}



### Error handling

Answer:

	{
	  "success": false,
	  "code": 20,
	  "message": "Invalid secret key"
	}

Error codes:

 * 10 - Module disabled
 * 20 - Invalid secret key


License
-------

This software is distributed under the [GNU GPL V3](http://www.gnu.org/licenses/gpl.html) License.
