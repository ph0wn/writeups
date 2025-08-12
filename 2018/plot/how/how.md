# How this challenge was created

1. With OpenSCAD, I designed the object (a red flag with the flag written on it). This was saved to file `flag.scad`. I used OpenSCAD 2015.03-1 (installed on Linux with `apt-get install openscad`)

2. I exported the object in STL: `flag.stl`.

3. Download Cura. I used Cura version 3.3.1. Install a new custom printer Anet A8. Import the STL project. For the print setup use [this high quality profile](https://www.thingiverse.com/thing:2442909).

4. Press "Prepare to Slice". My model says it will take 2h59 to print. Press save to file to create the GCode file.

# To remove comments from the GCode file

`sed -i '/^;/ d' strange.file`
