Date of creation: Jul 07, 2015. Last update: Jul 06, 2016.

I once put all of my phpBB extensions into one GitHub respository when I was a GitHub newbie. I am re-committing them for separate repositories.

# What is this?

A phpBB extension: DICEK BBCode.

Tested on phpBB 3.1.9.

For more advance dice please visit [hanelyp fancy dice](https://www.phpbb.com/community/viewtopic.php?f=456&t=2306161) extension. DICEK only works with integer and no more.

The difference: My extension saves the dice result into the database so that the user cannot cheat by re-dicing.

# Syntax

`[dicek]max1-max2-max3-...-maxn[/dicek]`

Result:

`[a random number from 1 to max1]-[a random number from 1 to max2]-...-[a random number from 1 to maxn]`

# Example

[dicek]10-20-30-20[/dicek] => 8-11-25-5

[dicek]3[/dicek] => 2

## Note

Once you submit the post with [dicek]10-20-30-20[/dicek], you can later edit the post into:

> First dicek with 30 max: [dicek]30[/dicek]

> First dicek with 20 max: [dicek]20[/dicek]

> Second dicek with 20 max: [dicek]20[/dicek]

So the result will be:

> First dicek with 30 max: 25

> First dicek with 20 max: 11

> Second dicek with 20 max: 5
