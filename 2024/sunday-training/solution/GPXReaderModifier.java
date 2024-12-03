import org.w3c.dom.*;
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.transform.*;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamResult;
import java.io.File;

public class GPXReaderModifier {
    public static void main(String[] args) {
        if (args.length < 3) {
            System.out.println("Usage: java GPXReaderModifier <input_gpx_file> <output_gpx_file> <string_values>");
            return;
        }

        String inputFilePath = args[0];
        String outputFilePath = args[1];
        String inputString = args[2];
        
        // Convert the input string to an array of ASCII values
        int[] asciiValues = new int[inputString.length()];
        for (int i = 0; i < inputString.length(); i++) {
            asciiValues[i] = (int) inputString.charAt(i);
        }

        try {
            // Load the GPX file
            File gpxFile = new File(inputFilePath);
            DocumentBuilderFactory dbFactory = DocumentBuilderFactory.newInstance();
            DocumentBuilder dBuilder = dbFactory.newDocumentBuilder();
            Document doc = dBuilder.parse(gpxFile);
            doc.getDocumentElement().normalize();

            // Get all track points
            NodeList trkptList = doc.getElementsByTagName("trkpt");
            int asciiIndex = 0;

            for (int i = 0; i < trkptList.getLength(); i++) {
                Node trkpt = trkptList.item(i);

                if (trkpt.getNodeType() == Node.ELEMENT_NODE) {
                    Element trkptElement = (Element) trkpt;
                    NodeList extensionsList = trkptElement.getElementsByTagName("extensions");

                    if (extensionsList.getLength() > 0) {
                        Element extensionsElement = (Element) extensionsList.item(0);
                        NodeList hrList = extensionsElement.getElementsByTagName("gpxtpx:hr");

                        if (hrList.getLength() > 0) {
                            Element hrElement = (Element) hrList.item(0);
                            // Change the value of "hr" using the ASCII values from the input string
                            hrElement.setTextContent(String.valueOf(asciiValues[asciiIndex]));
                            
                            // Update the index and wrap around if necessary
                            asciiIndex = (asciiIndex + 1) % asciiValues.length;
                        }
                    }
                }
            }

            // Save the modified GPX document
            TransformerFactory transformerFactory = TransformerFactory.newInstance();
            Transformer transformer = transformerFactory.newTransformer();
            transformer.setOutputProperty(OutputKeys.INDENT, "yes");
            DOMSource source = new DOMSource(doc);
            StreamResult result = new StreamResult(new File(outputFilePath));
            transformer.transform(source, result);

            System.out.println("GPX file modified and saved successfully.");
        } catch (Exception e) {
            e.printStackTrace();
        }
    }
}
