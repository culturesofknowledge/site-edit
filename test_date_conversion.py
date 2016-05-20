from __future__ import print_function

from tweaker import tweaker

tweaker = tweaker.DatabaseTweaker( )

def test( new_date, correct_day, correct_month, correct_year ) :
	if new_date["d"] != correct_day :
		return False
	if new_date["m"] != correct_month :
		return False
	if new_date["y"] != correct_year :
		return False
	return True

def test_print( new_date, correct_day, correct_month, correct_year ) :

	right = test( new_date, correct_day, correct_month, correct_year )
	if not right :
		print( new_date, correct_day, correct_month, correct_year )

	return right

error = 0
# 10 day difference 1700
if not test_print( tweaker.calendar_julian_to_calendar_gregorian( 1, 1, 1700 ), 11, 1, 1700 ) :
	print( "1 Error, 10 day difference 1700" )
	error += 1

# 11 day difference 1700
if not test_print( tweaker.calendar_julian_to_calendar_gregorian( 29, 2, 1700 ), 11, 3, 1700 ) :
	print( "2 Error, 11 day difference 1700" )
	error += 1

# 1777 day change
if not test_print( tweaker.calendar_julian_to_calendar_gregorian( 7, 7, 1777 ), 18, 7, 1777 ) :
	print( "3 Error, 1777 day change" )
	error += 1

# 1777 month and day change
if not test_print( tweaker.calendar_julian_to_calendar_gregorian( 27, 7, 1777 ), 7, 8, 1777 ) :
	print( "4 Error, 1777 month and day change" )
	error += 1


# 1777 year, month and day change
if not test_print( tweaker.calendar_julian_to_calendar_gregorian( 27, 12, 1777 ), 7, 1, 1778 ) :
	print( "5 Error, 1777 year, month and day change" )
	error += 1


if error == 0:
	print( "No errors" )
else :
	print( "Some errors!", error )