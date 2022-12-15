/* By JuJa */

/* define the key profile according to the plan */
/* width of the profile, lenght of the profile */ 
key_profile = [ [2   ,10  ],
                [1.5 ,6.5 ],
                [1.5 ,7   ],
                [1.5 ,10  ],
                [1.5 ,4   ],
                [1.5 ,5   ]];

/* make recusive function to calcualte the offset */ 
function offset(key_profile, i ) =  i > 0 ?  key_profile[i-1][0] + offset(key_profile, i-1 )  : 0; 

module profile( l , h ){
    diameter=5;
    rotate ([90,0,90]){
       cylinder(d=diameter , h=h, center=true,$fn=20);
       translate([l/2,0,0]) cube([l,diameter,h],center=true);
    }
}


/* generate the profile */
module full_profile(){
    for ( i = [0 : len(key_profile)-1] ){    
      echo ( offset(key_profile,i)  );
      translate([  offset(key_profile, i ) + key_profile[i][0]/2, 0 , 0]) 
      profile( key_profile[i][1] , key_profile[i][0] );
    }
}

//define the size of the element
cube_x= 8;
cube_y=15;
cube_z= 5;

cylinder_d=5;
cylinder_h=17;

hole_d=3;
hole_h=3;


difference(){
   union(){
        translate([cylinder_h +cube_x/2,0,0])
        cube([cube_x,cube_y,cube_z],center=true);
        
        translate([cylinder_h/2,0,0])
        rotate ([90,0,90])cylinder( d=cylinder_d , h=cylinder_h, center=true,$fn=20);

        full_profile();
       
   }  
   
    translate([hole_h/2,0,0])
    rotate ([90,0,90])
    cylinder(d=hole_d , h=hole_h, center=true,$fn=20);
}   
