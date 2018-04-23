# "RCKT Engine" Active Record Model
A better active record implementation for CodeIngiter 3. Supports easy initialization, just-in-time methods, and post load formatting/calculations.

---

## Installation

Copy `application/core/RCKT_Model.php` into your `application/core` directory.

## Model Setup

To implement the active record system, simply create a new model using the basic structure below. Make sure to extend the RCKT_Model class instead of the typical CI_Model.

```
class product_model extends RCKT_Model {
	
	// Setup
	const DB_TABLE = 'myproductstable'; // This is the name of your table in your database
	const DB_TABLE_PK = 'productid'; // This is the name of the primary key column
	const DB_FIELDS_SETUP = array('title','price','description'); // This array contains any columns you want to make editable
	
}
```
---

## Working with a Model

### Before you Begin

Make sure you've either added the new model to your autoload, or that you're calling it in the specific controller you plan to work in. Otherwise CI won't load it.

#### Loading a Record

You can either pass an integer representing the primary key in the database for a particular product, or an array of properties to search for the record.

```
// Load using ID
$product = new Product_model(17);

// Search using Array
$search = array("title" => "My Product", "price" => "50.00");
$product = new Product_model($search);

// Error Handling
if( ! $product){
  // Could not load product ID #17 or find using search
}
```

#### Creating / Editing Records

Creating a record is as simple as creating a new object without passing an ID. Then to edit, simply change the properties of the object and call the save() function. Only columns (properties) declared in DB_FIELDS_SETUP can be saved back to the database.

```
// Create a New Object
$product = new Product_model();

// Change Some Properties (each matching a column name in my database)
$product->title = "My New Product";
$product->description = "I just added this product";
$product->price = 50;

// Save it to the Database
$product->save();
```

#### Updating Records

Updating records is just as easy, just load the record first.

```
// Load my Product
$product = new Product_model(17);

// Change Some Properties (each matching a column name in my database)
$product->title = "A New Name for My Product";
$product->price = 75;

// Save it to the Database
$product->save();
```
**Notice we use the same save() function to push the data back to the database.** RCKT Engine will automatically determine whether to insert or update the database based on whether you've already loaded an existing record into the object.

---

Used extensively at https://www.telemetric.io/ - a free tool to monitor MySQL databases daily.
