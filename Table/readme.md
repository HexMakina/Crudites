# Table
## Description
Description objects stores everything related to the structure of the table, but offers no manipulation methods

## Manipulation
Manipulation extends Description to provide manipulation tools:

### Query based methods
+ insert()
+ update()
+ delete()
+ select()

all returning a `QueryInterface` object

### Record based methods
+ produce(array), return a new record, 
+ restore(array), return an existing record

all returning a `RowInterface` object based provided on associative data

# Row

# Column
## Column type
