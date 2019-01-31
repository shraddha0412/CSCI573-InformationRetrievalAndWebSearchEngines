import java.io.File;
import java.io.FileWriter;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.BufferedWriter;
import java.io.IOException;
import java.util.ArrayList;
import java.util.Arrays;
import org.apache.tika.exception.TikaException;
import org.apache.tika.parser.html.HtmlParser;
import org.apache.tika.sax.BodyContentHandler;
import org.apache.tika.metadata.Metadata;
import org.apache.tika.parser.ParseContext;
import org.xml.sax.SAXException;

public class BigText {

	private static BufferedWriter writer;

		public static void parseFiles(String dirPath)
			throws FileNotFoundException, IOException, SAXException, TikaException {
		File dir = new File(dirPath);
		File[] files = dir.listFiles();
		ArrayList<String> fullList = new ArrayList<String>();
		for (File x : files)
			fullList.addAll(parseFile(x));
		writeFile(fullList);
	}
	
	public static ArrayList<String> parseFile(File myFile) throws FileNotFoundException, IOException, SAXException, TikaException {
		FileInputStream inputstream = new FileInputStream(myFile);
		Metadata metadata = new Metadata();
		BodyContentHandler bodyContentHandler = new BodyContentHandler(-1);
		HtmlParser htmlparser = new HtmlParser();
		ParseContext parsecontext = new ParseContext();		
		htmlparser.parse(inputstream, bodyContentHandler, metadata, parsecontext);
		String bodyString = bodyContentHandler.toString();
		ArrayList<String> bigTextList = new ArrayList<String>(Arrays.asList(bodyString.split("\\W+")));
		return bigTextList;
	}
	
	public static void writeFile(ArrayList<String> wordList) throws IOException {
		writer = new BufferedWriter(new FileWriter("big.txt"));
		for (String x : wordList)
			writer.write(x + "\n");
	}

	public static void main(String args[]) throws FileNotFoundException, IOException, SAXException, TikaException {
		String dirPath = "C:\\Users\\shrad\\Downloads\\mercurynews\\mercurynews";
		parseFiles(dirPath);
	}
}