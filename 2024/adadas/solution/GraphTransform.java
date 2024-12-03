import java.io.*;

public class GraphTransform {


    public static String loadFileData(File file) {
        char[] ba;
        try {
            InputStreamReader isr = new InputStreamReader(new FileInputStream(file), "UTF8");
            FileInputStream fis = new FileInputStream(file);
            int nb = fis.available();

            ba = new char[nb];
            isr.read(ba, 0, nb);
            fis.close();
            isr.close();
        } catch (Exception e) {
            return null;
        }
        return new String(ba);
    }


    public static void main(String[] args) {

        if (args.length < 2) {
            System.out.println("usage: java GraphTransform inputfile outputfile");
            System.exit(-1);
        }

        try (BufferedReader reader = new BufferedReader(new FileReader(args[0]));
             BufferedWriter writer = new BufferedWriter(new FileWriter(args[1]))) {
            String line;

            // Read the file line by line and write each line to the output file
            while ((line = reader.readLine()) != null) {
                
                line = line.replace("[0...0]", "");
                line = line.replace(" ", "");
                line = line.replace("_", "");
                
                int index = line.indexOf("i(");
                if (index > -1) {
                    String head = line.substring(0, index);
                    String tail = line.substring(index+2, line.length());
                    tail = tail.replace(")\"", "\"");
                    line = head + tail;
                }

                writer.write(line);
                writer.newLine(); // This adds a newline after each line is written
            }
        } catch (IOException e) {
            System.out.println("An error occurred:");
            e.printStackTrace();
        }

    



        /*String reversed = "";
        for (int i = 0; i < data.length(); i++) {
            char c = data.charAt(i);
            if (c == '0') {
                reversed += "1";
            } else if (c == '1'){
                reversed += "0";
            } else {
                reversed += c;
            }
        }


        String list[] = reversed.split(" ");
        String solution = "";
        for(int i=0; i<list.length; i++) {
            String binaryString = list[i];
            System.out.println("Parsing binary:" + binaryString);
            int decimal = Integer.parseInt(binaryString,2);
            System.out.println("Decimal:" + decimal);
            solution += Character.toString ((char) decimal);
        }
        System.out.println("Solution: " + solution);*/


    }


}
