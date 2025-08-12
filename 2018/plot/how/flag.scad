*color("red") cube([100,30,30]);
color ("red") linear_extrude (3) { 
    polygon(points=[[0, -30],[0,30],[150,0]]);
}
translate([5, 10, 3]) {
    color("white") {
linear_extrude(1) text (
  "ph0wn{",
  font = "Courier New; 
  Style = Bold", 
  valign = "center", 
  halign = "left",
  language = "fr"
);
}
}
translate([5, -6, 3]) {
    color("white") {
linear_extrude(1) text (
  "MyAnetRuleZ",
  font = "Courier New; 
  Style = Bold", 
  valign = "center", 
  halign = "left",
  language = "fr"
);
}
}
translate([5, -20, 3]) {
    color("white") {
linear_extrude(1) text (
  "}",
  font = "Courier New; 
  Style = Bold", 
  valign = "center", 
  halign = "left",
  language = "fr"
);
}        
}

