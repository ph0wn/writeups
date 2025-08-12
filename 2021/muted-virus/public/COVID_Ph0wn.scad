

mainA = [ 
    [45,120,6,119,120],
    [50,330,14,95,30],
    [8,90,5,104,30],
    [4,120,14,115,30],
    [54,30,6,110,90],
    [8,240,16,52,210],
    [15,60,10,51,120],
    [51,90,17,125,240],
    [5,30,9,110,210],
    [50,30,17,68,180],
    [6,60,5,112,60],
    [124,30,8,67,240],
    [120,60,11,67,120],
    [11,120,5,48,300],
    [128,90,8,52,300],
    [123,210,7,123,90],
    [2,240,14,73,60],
    [1,210,15,98,240],
    [6,150,13,108,150],
    [1,210,14,95,210]];

    
difference() {
        union() {
            color("red")
                sphere(20, $fn=100); 
            spikes(30, 30);       
     
        }
}


module flatSector(radius, angles, fn = 24) {
    r = radius / cos(180 / fn);
    step = -360 / fn;

    points = concat([[0, 0]],
        [for(a = [angles[0] : step : angles[1] - 360]) 
            [r * cos(a), r * sin(a)]
        ],
        [[r * cos(angles[1]), r * sin(angles[1])]]
    );

    difference() {
        circle(radius, $fn = fn);
        polygon(points);
    }
}

module spikes(z1, z2) {
    cpt = 0;
    
     for ( x = [0 : z1 : 360] ){
         for ( y = [30 : z2 : 330] ){
            a = 1;
            b = 10;
            c = 0;
            d = 10;
            e = 10;
            r = 1;
            g = 0.45;
            bl = 0.1;
            aa = 1;
            color([1/(125-b), g, bl, aa])
                rotate([x,y,0])
                    cylinder(29, 1.9, true);
             
            for(ind = [0:19]) {
                 if  ( (mainA[ind][1] == x) && (mainA[ind][4] == y) ) {
                     a = mainA[ind][2];
                     b = mainA[ind][3];
                     d = mainA[ind][0];
                     e = mainA[ind][4];
                     echo(b);
                     color([1/(126-b), 0.45, 0.1, aa])
                        rotate([x,y,0])
                            cylinder(30+a/10, 2+d/30, true);
                     
                 }
            }
         }
     }
                
}



module half_cylinder(r, h, angle) {
    steps = $fn > 0 ? $fn : ceil(max(min(360 / $fa, 2 * PI * r / $fs), 6));
    linear_extrude(h) {
        points = [for (i = [0:steps]) [cos(i / steps * angle) * r, sin(i / steps * angle) * r]];
        polygon(points);
    }
}
                
 

