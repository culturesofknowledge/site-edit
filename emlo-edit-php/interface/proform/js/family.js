var relationships = ["adoptiveFatherOf", 
            "adoptiveMotherOf", 
            "ancestorOf", 
            "auntOf", 
            "biologicalFatherOf", 
            "biologicalMotherOf", 
            "brotherInLawOf", 
            "brotherOf", 
            "daughterOf", 
            
            "descendentOf", 
            "fatherInLawOf", 
            "fatherOf", 
            "grandParentOf", 
            "grandfatherOf", 
            "grandmotherOf", 
            "greatGrandParentOf", 
            "greatGrandfatherOf", 
            "greatGrandmotherOf", 
            "hasAdoptiveFather", 
            "hasAdoptiveMother", 
            "hasAncestor", 
            "hasAunt", 
            "hasBiologicalFather", 
            "hasBiologicalMother", 
            "hasBrother", 
            "hasBrotherInLaw", 
            "hasChild", 
            "hasCousin", 
           
            "hasDaughter", 
            "hasDeath", 
            "hasDescendent", 
           
            "hasFather", 
            "hasFatherinLaw", 
           
            "hasGrandParent", 
            "hasGrandchild", 
            "hasGranddaughter", 
            "hasGrandfather", 
            "hasGrandmother", 
            "hasGrandson", 
            "hasGreatAunt", 
            "hasGreatGrandParent", 
            "hasGreatGrandchild", 
            "hasGreatGrandfather", 
            "hasGreatGrandmother", 
            "hasGreatUncle", 
            "hasHusband", 
            "hasInLaw", 
          
            "hasMother", 
            "hasMotherInLaw", 
            "hasNephew", 
            "hasNiece", 
            "hasParent", 
          
            "hasRelation", 
          
            "hasSibling", 
            "hasSister", 
            "hasSisterInLaw", 
            "hasSon", 
            "hasSpouse", 
            
            "hasUncle", 
            "hasWife", 
            "husbandOf", 
            "isBloodRelationOf", 
            "isChildOf", 
            "isGrandchildOf", 
            "isGranddaughterOf", 
            "isGrandsonOf", 
            "motherInLawOf", 
            "motherOf", 
            "nephewOf", 
            "nieceOf", 
            "parentOf", 
            "sisterInLawOf", 
            "sisterOf", 
            "sonOf", 
            "uncleOf", 
            "wifeOf"];


$(function() {
                
$( "#FamilyRelationships-FamilyRole" ).autocomplete({
       source: relationships
  });
});
            
            
        
        
