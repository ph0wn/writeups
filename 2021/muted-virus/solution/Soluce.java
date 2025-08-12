import java.util.*;

public class Soluce {

    public void classify() {
	ArrayList<Spike> spikes = new ArrayList<>();
	
	for(int i=0; i<mainA.length; i++) {
	    //System.out.println("Adding splike");
	    Spike spike = new Spike(mainA[i]);
	    spikes.add(spike);
	}

	String s = "";
	for(Spike spike: spikes) {
	    s = s + spike.color;
	}
	System.out.println("Non classified colors:");
	System.out.println(s);

	Collections.sort(spikes);

	s = "";
	for(Spike spike: spikes) {
	    s = s + spike.color;
	}
	System.out.println("Classified colors:");
	System.out.println(s);


	Collections.shuffle(spikes);
	s = "";
	for(Spike spike: spikes) {
	    s = s + spike.color;
	}
	System.out.println("Shuffled colors:");
	System.out.println(s);

	for(Spike spike: spikes) {
	    System.out.println("[" + spike.thickness + "," + spike.posX + "," + spike.length + "," + (int)(spike.color) + "," + spike.posY + "]");
	}
	

	
	Collections.sort(spikes);
	s = "";
	for(Spike spike: spikes) {
	    s = s + spike.color;
	}
	System.out.println("Classified colors:");
	System.out.println(s);

	
    }

    private static int [][] mainA = {
	{45,120,6,119,120},
	{50,330,14,95,30},
	{8,90,5,104,30},
	{4,120,14,115,30},
	{54,30,6,110,90},
	{8,240,16,52,210},
	{15,60,10,51,120},
	{51,90,17,125,240},
	{5,30,9,110,210},
	{50,30,17,68,180},
	{6,60,5,112,60},
	{124,30,8,67,240},
	{120,60,11,67,120},
	{11,120,5,48,300},
	{128,90,8,52,300},
	{123,210,7,123,90},
	{2,240,14,73,60},
	{1,210,15,98,240},
	{6,150,13,108,150},
	{1,210,14,95,210}
	};

    
    
    public static void main(String[] args) {
	Soluce soluce = new Soluce();
	soluce.classify();
    }

    private class Spike implements Comparable<Spike>{
	
	public int posX, posY, length, thickness;
	public char color;
	
	public Spike(int[] infos) {
	    if (infos.length < 5) 
		return;
	    
	    posX = infos[1];
	    posY = infos[4];
	    length = infos[2];
	    thickness = infos[0];
	    color = (char)(infos[3]);
	}

	public int compareTo(Spike s){
	    if (s.length != length) {
		return length - s.length;
	    }

	    return thickness - s.thickness;
		
	}	
    }

    
    
}


