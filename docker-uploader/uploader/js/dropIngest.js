// ignore the ingestname for now
prefix="collect_";
importTab =  new Object();
importTab.works  =  db.getCollection( prefix + "works");
importTab.manifestations  =  db.getCollection( prefix + "manifestations");
importTab.people  =  db.getCollection( prefix + "people");
importTab.places  =  db.getCollection( prefix + "places");
importTab.repositories  =  db.getCollection( prefix + "repositories");

for(property in importTab) {
  print(property + " = " + importTab[property]);
	importTab[property].drop();						// 01
}