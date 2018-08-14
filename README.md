EMLO Tweaker
===========

Make changes directly in the database.


Use the tweaker code to make changing options easier. Some examples:

To connect to the tweaker
```python
tweaker = tweaker.DatabaseTweaker( postgres_connection )
```

To open a csv
```python
tweaker.get_csv_data( csv_file )
```

To grab a work use
```python
tweaker.get_work_from_iwork_id( csv_row["workid"] )
```

To update a work use:
```python
tweaker.update_work( csv_row['iwork_id'], { "original_calendar" : csv_row["calendar"] } )
```

See the tweaker file for the complete list of helpers, including works, resources, relationships
