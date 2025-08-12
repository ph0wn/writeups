# Overall idea

Each bar codes one character of the flag, through the decimal value of its ASCII code. To decrypt the flag, we need to convert the harmonies into pitch-class sets, and then to perform two sums: first, a sum of the elements of each set; second, a sum of these computed sums.



# Description of the decryption algorithm

The algorithm works as follow:

For each bar:
1. For each harmony, convert it (including single-note "harmonies") into its corresponding pitch-class set. At the end of this step, we obtain an array of integer sets.
2. Sum the elements of this array, for instance by summing the elements of the sets and then by additionning the resulting numbers one to another. The final number is the ASCII decimal value of the desired character.


# Examples

1. For the first bar, the sequence of pitch-class sets is <{B,G,A#,C#,F} , {A#,B} , {B,C}, {B}, {B}, {B}, {F#}, {G}>, thus <{11,7,10,1,5} , {11,10} , {11,0}, {11}, {11}, {11}, {6}, {7}> in integer notation.
11 + 7 + 10 + 1 + 5 + 11 + 10 + 11 + 0 + 11 + 11 + 11 + 6 + 7 = 112. In ASCII code, 112 corresponds to the character "p": therefore the first bar codes "p".

2. For the second bar, the sequence of pitch-class sets is <{C,B}, {B}, {A#}, {A}, {E,F#,G#}, {E}, {A}, {A#}, {B}, {A#,C#}>, thus <{0,11}, {11}, {10}, {9}, {4,6,8}, {4}, {9}, {10}, {11}, {10,1}> in integer notation.
0 + 11 + 11 + 10 + 9 + 4 + 6 + 8 + 4 + 9 + 10 + 11 + 10 + 1 = 104. In ASCII code, 104 corresponds to the character "h": therefore the second bar codes "h".


# Full solution
3. < {C}, {A#,B}, {B,C}, {D,Eb,F,Gb} >
0+11+10+11+0+2+3+5+6 -> 48. "0"

4. < {D,Eb,F,Gb}, {D,Eb,F,Gb}, {D,Eb,F,Gb}, {D,Eb,F,Gb,A#,B}, {A#,C#}, {C#}, {B}, {C#,D,Eb,F} >
< {2,3,5,6}, {2,3,5,6}, {2,3,5,6}, {2,3,5,6,10,11}, {10,1}, {1}, {11}, {1,2,3,5} > -> 119. "w"

5. < {B}, {B}, {C}, {C}, {B}, {B}, {C}, {C}, {B}, {B}, {B}, {B}, {B}, {B} > 
11 + 11 + 0 + 0 + 11 + 11 + 0 + 0 + 11 + 11 + 11 + 11 + 11 + 11 -> 110. "n"

6. < {B}, {B}, {A#}, {A}, {A}, {A#}, {B}, {B}, {A,B,G,E,A} >
11+11+10+9+9+10+11+11+10+11+7+4+9 -> 123. "{"

7. < {B}, {A#}, {A}, {G#,D,F,E,C}, {B}, {C#}, {A#} >
11+10+9+8+2+5+4+0+11+1+10 -> 71. "G"

8. < {C}, {A#,B}, {B,C}, {D,Eb,F,Gb} >
0+11+10+11+0+2+3+5+6 -> 48. "0"

9. < {D,Eb,F,Gb}, {B,C}, {A#,B}, {C} >
0+11+10+11+0+2+3+5+6 -> 48. "0"

10. < {C}, {B}, {Bb}, {A}, {B}, {B,C,D#}, {G,A,B}, {B,C,D#}, {E} >
0+11+10+9+11+11+0+3+7+9+11+11+0+3+4 -> 100. "d"

11. < {E,F,G}, {C,D,E}, {E,F,G}, {E,G,C}, {A,B,F} >
4 + 5 + 7 + 0 + 2 + 4 + 4 + 5 + 7 + 4 + 7 + 0 + 9 + 11 + 5 -> 74. "J"

12. < {C}, {A#,B}, {B,C}, {D,Eb,F,Gb} >
0+11+10+11+0+2+3+5+6 -> 48. "0"

13. < {D,Eb,F,Gb}, {D,Eb,F,Gb}, {D,Eb,F,Gb}, {D,Eb,F,Gb}, {D,Eb,F,Gb}, {D,Eb,F,Gb}, {D} >
2+3+5+6+2+3+5+6+2+3+5+6+2+3+5+6+2+3+5+6+2+3+5+6+2 -> 98. "b"

14. < {D,Eb,F,Gb}, {D,Eb,F,Gb}, {D,Eb,F,Gb}, {D,Eb,F,Gb}, {B,D} >
2+3+5+6+2+3+5+6+2+3+5+6+2+3+5+6+11+2 -> 77. "M"

15. < {B,D}, {A#,B}, {A#,B}, {B,D}, {A#,B}, {B,D}, {B,D}, {D} >
11+2+10+11+10+11+11+2+10+11+11+2+11+2+2 -> 117. "u"

16. < {Eb}, {B,D}, {B,D}, {B,A#}, {B,D}, {A#,B}, {A#,B}, {A#} >
3+11+2+11+2+11+10+11+2+10+11+10+11+10 -> 115. "s"

17. < {D}, {C#}, {C}, {B}, {B}, {B}, {B}, {B}, {B}, {B}, {B}, {B}, {C}, {C#}, {D} >
2+1+0+11+11+11+11+11+11+11+11+11+0+1+2 -> 105. "i"

18. < {Eb}, {D,Eb,F,Gb}, {D,Eb,F,Gb}, {D,Eb,F,Gb}, {D,Eb,F,Gb}, {D,Eb,F,Gb}, {D,Eb,F,Gb} >
3+2+3+5+6+2+3+5+6+2+3+5+6+2+3+5+6+2+3+5+6+2+3+5+6 -> 99. "c"

19. < {D}, {C#}, {C}, {B}, {B}, {B}, {B}, {B}, {B}, {B}, {B}, {B}, {C}, {C#}, {D} >
2+1+0+11+11+11+11+11+11+11+11+11+0+1+2 -> 105. "i"

20. < {D,Eb,F,Gb}, {D,Eb,F,Gb}, {D,Eb,F,Gb}, {D,Eb,F,Gb}, {D,Eb,F,Gb}, {D,Eb,F,Gb}, {C#} >
2+3+5+6+2+3+5+6+2+3+5+6+2+3+5+6+2+3+5+6+2+3+5+6+1. "a"

21. < {B}, {B}, {C}, {C}, {B}, {B}, {C}, {C}, {B}, {B}, {B}, {B}, {B}, {B} > 
11+11+0+0+11+11+0+0+11+11+11+11+11+11 -> 110. "n"

22. < {B}, {B}, {B} >
11+11+11 -> 33. "!"

23. < {C}, {C#}, {D}, {D#}, {E}, {F}, {F#}, {G}, {G#}, {A}, {A#}, {B}, {B}, {B}, {B}, {B}, {E}, {B} >
0+1+2+3+4+5+6+7+8+9+10+11+11+11+11+11+4+11






