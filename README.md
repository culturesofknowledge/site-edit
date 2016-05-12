EMLO Exporter
=============

Export works and there associated objects (locations, people, manifestations, institutions, resources).

How to
------

You can see numerous exampls of how to use it, but basically:

1. Setup a config.py file in the root directry to connect to a postgres database:
```python
config = dict(
	dbname="NAME",
	user="USER",
	host="localhost",
	password="PASSWORD",
	port="5432"
)
```

2. Create a postgres connection string 
```python
postgres_connection = "dbname='" + config["dbname"] + "'" \
                      + " host='" + config["host"] + "' port='" + config["port"] + "'" \
                      + " user='" + config["user"] + "' password='" + config["password"] + "'"
```

3. Create a list of work ids you want to extract. (for instance a csv, or run a query to to extract those for a particular catalogue)
```python
e = Exporter( postgres_connection, False, debug_on )

command = "select work_id from cofk_union_work where original_catalogue='HARTLIB'"
work_ids = e.select_all( command )
work_ids = [id['work_id'] for id in work_ids]
```

4. Pass the work ids into the exporter and specify a folder:
```python
e.export( work_ids, "my folder" )
```

