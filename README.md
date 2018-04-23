# "RCKT Engine" Active Record Model
**A better active record implementation for CodeIgniter 3.**

CodeIgniter provides a query builder library for abstracting database access inside of models, but no overall method by which to implement direct Active Record, meaning, a one-to-one relationship between models and their respective database tables.

RCKT Engine is an active model built for CodeIgniter that's been tried and tested to scale on some rather large and complex projects. The idea is development speed - using a one-to-one relationship while adding additional functionality such as post load formatting and just-in-time data processing.

#### Core features of RCKT Engine's Active Record Model:

* Easy initialization (get setup in seconds)
* Deep caching (minimize database queries)
* Automatic formatting (do less work)
* Just-in-time methods (only get data if it's needed)
* Post load formatting/calculations (adjust your data before you use it)

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

#### Loading a Record from the Database

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

#### Populating a Record from PHP Data

Sometimes you've already got the data within CI, and don't want to make another trip to your database by calling load(ID). In this case, you can use the populate() method to create an object using local data. Usually this is done when you're already looping through rows from a database query.

```
$query = $this->db->query("YOUR SELECT QUERY");

foreach ($query->result() as $row){
	$product = new Product_model();
	$product->populate($row);
	// Make some changes
	$product->save();
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

Updating records is easy - just load the record, make changes, and save it.

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

#### Deleting Records

To delete a record, simply load it and call the delete() method.

```
// Load my Product
$product = new Product_model(17);

// Delete it
$product->delete();
```

---

## Formatting and Calculations (Post_Load)

Sometimes you'll want to run some logic after loading a record from the database. For example, we might want to create a pretty currency-formatted version of our product pricing every time we load a product. To do so, create a post_load() function in your model.

```
public function post_load(){
	return TRUE;
}
```
This method must return TRUE - or else the load will not complete. If it returns FALSE, it will be assumed the data for the selected record did not pass some sort of validation in post_load and it will not be available for further usage/editing. In that case, the entire object will simply return FALSE;

Here's an example where we format the title of the product and the price:
```
public function post_load(){
	$this->formatted_title = ucwords(strtolower($this->title));
	$this->formatted_price = "$".number_format($this->price, 2, '.', '');
	return TRUE;
}
```
**Note: Do not replace your original properties unless you are okay with the updated value getting saved back to the database if you were to call save() later.** For example, you wouldn't want to save formatted_price back to price, as it would convert the float value to a string in your database - and likely cause errors. I'd recommend prefixing modified values with either `formatted_` or `display_` so that you can keep them separate from native columns.

You can also create properties with names that don't match your database columns. Only properties declared as fields in your DB_FIELDS_SETUP array will ever be saved back to the database.

---

## JIT (Just-in-Time) Methods

Often times you want to perform extensive calculations, or pull data from associated tables, after loading a record from the database - but this can be CPU intensive and time-consuming to perform every time you load the data. That's where JIT (just-in-time) methods come in.

JIT methods work similarly to the post_load, in that you can perform calculations, format data, or do anything you want - and they return TRUE if successful. **The big difference between post_load and JIT methods is that post_load gets called instantly every time, versus JIT getting called manually before you need the data.**

Setting up a JIT method looks like this in your model:

```
public function JIT_get_all_associated_transactions(){
	// Heavy calculations or data loading, such as loading an array of 1,000 transactions where people purchased this product
	$this->transactions = array(...)
	// return TRUE;
}
```
**To make any method a JIT method, just prefix it with JIT_ and return TRUE**

Then you would call the function by simply calling (without the prefix):
```
$myrecord->get_all_associated_transactions();
```

JIT methods return a reference to the object itself, so you can chain the request.

Here's an example where we don't need transaction data, so we don't load the JIT function

```
// Don't need JIT, just looking up basic product details (maybe for a product listing page)
$product = new Product_model(17);
```

And now we need the transaction data, so we can use JIT to get that...

```
// So let's do this more resource-intensive task as JIT
foreach($product->get_all_associated_transactions()->transactions as $transaction){
	// Do something with each transaction
}

```

JIT method formatting is cached after the first time it's called, so you can called it 100 times in a row and don't need to worry about it re-doing the work.

## Automatic Formatting

RCKT Engine automatically formats some properties when it sees them as columns in your database. Those include first name `$this->first`, last name `$this->last`, email `$this->email`, and phone number `$this->phone`.

It also automatically creates an ID property `$this->ID` that is equal to your primary key ID, so that you can always write code using `$myproduct->ID` regardless of what your primary key is called.

Lastly, a html-encoded JSON representation of the data is created under the property `$this->JSON_REPRESENTATION`. You could use this to provide simply API access or to easily move data to client-side programming such as jQuery.

## Deep Caching

An extensive data cache is built into RCKT Engine, and doesn't require any additional work on your part. Whenever an object is created, a reference to it's table and ID is stored in memory. Whenever you attempt to load that table and ID again, it will pull the object from the cache, instead of from your database.

For example:
```
$product = new Product_model(17);
$product = new Product_model(17);
$product = new Product_model(17);
$product = new Product_model(17);
$product = new Product_model(17);

// Only one query to the database was made. The other 4 requests used the cached version.
```
The cache will work when using load() from either primary key ID or from a search array. The populate method does not use the caching system, as the data is already loaded locally. JIT functions are also cached after they run once.

---
Please feel free to submit any pull requests, and enjoy! Also, check out our other free project for software developers over at https://www.telemetric.io/ - a free tool to monitor MySQL databases daily.
