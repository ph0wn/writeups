MODULE Test;
IMPORT Files, Greet;
VAR i   : CARDINAL;
    file: Files.FILE;
    rec : Greet.FileRecord;
BEGIN
  IF Files.Open(file, "database.bin") THEN
    FOR i:=1 TO 5 DO
      Files.ReadRec(file, rec);
      Greet.Encode(rec.id);  WRITELN("Id: ",rec.id);
      Greet.Encode(rec.pwd); WRITELN("Pwd: ", rec.pwd);
      Greet.Encode(rec.greeting); WRITELN("Greeting: ",rec.greeting);
    END
  END
END Test.
