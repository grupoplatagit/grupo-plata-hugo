// ============================================================
// PROSPECTOS — Estudios Contables CABA + GBA
// 200 registros reales de Google Maps
// Sin web registrada en Google Maps = prioridad ★★★
// JP Market Pro — Juan Pablo Colasurdo
// ============================================================

const PROSPECTOS_200 = [

  // ── CABA · MICROCENTRO / SAN NICOLÁS / SAN TELMO ─────────
  { nombre: "Estudio Contable Lugones",                  dir: "Carlos Pellegrini 651 6°",          zona: "Microcentro",       tel: "+54 11 3985-8898"   },
  { nombre: "Estudio Contable FAR",                      dir: "Reconquista 379",                   zona: "Microcentro",       tel: "+54 11 5272-6133"   },
  { nombre: "CVB Consultoría",                           dir: "Cerrito 260 Piso 1",                zona: "Microcentro",       tel: "+54 11 5258-8252"   },
  { nombre: "Estudio Contable S&G",                      dir: "Av. Córdoba 937 Piso 4",            zona: "Microcentro",       tel: "+54 11 2618-9955"   },
  { nombre: "Estudio Logya",                             dir: "Lavalle 1763",                      zona: "Microcentro",       tel: "+54 11 2253-1562"   },
  { nombre: "Estudio Ottonello, Yunger & Asoc.",         dir: "Paraná 425",                        zona: "Microcentro",       tel: "+54 11 5032-2080"   },
  { nombre: "Microcentro Contable",                      dir: "Av. Corrientes 1585",               zona: "Microcentro",       tel: "+54 9 11 7600-7392" },
  { nombre: "Lozano Flores – Microcentro",               dir: "Esmeralda 950",                     zona: "Microcentro",       tel: "+54 11 5402-1644"   },
  { nombre: "Estudio Contable Herrera & Asoc.",          dir: "Florida 820 Piso 4",                zona: "Microcentro",       tel: "+54 11 4313-2278"   },
  { nombre: "Estudio Contable Suárez",                   dir: "Av. Corrientes 940",                zona: "Microcentro",       tel: "+54 11 4325-0094"   },
  { nombre: "Estudio Contable Parisi",                   dir: "Tucumán 1450 Piso 2",               zona: "Microcentro",       tel: "+54 11 4371-4420"   },
  { nombre: "G|L Estudio Contable",                      dir: "Piedras 1399 4G",                   zona: "San Telmo",         tel: "sin teléfono"       },
  { nombre: "LeMa Contadores",                           dir: "Tacuarí 1353 2do G",                zona: "San Telmo",         tel: "+54 11 4300-9364"   },
  { nombre: "Contadores Asociados Estudio Contable",     dir: "Humberto 1° 985 2°1",               zona: "San Telmo",         tel: "+54 11 6744-1226"   },
  { nombre: "Estudio Contable Dr. Carbone",              dir: "Piedras 1399 Piso 1 B",             zona: "San Telmo",         tel: "+54 9 11 6660-9889" },

  // ── CABA · CONGRESO / BALVANERA / CONSTITUCIÓN ───────────
  { nombre: "ACP Estudio Jurídico Contable",             dir: "Av. Entre Ríos 1243",               zona: "Congreso",          tel: "+54 11 5760-7128"   },
  { nombre: "MGM Estudio Contable",                      dir: "Constitución 1208",                 zona: "Constitución",      tel: "sin teléfono"       },
  { nombre: "Lozano Flores – Constitución",              dir: "Av. Juan de Garay 1272",            zona: "Constitución",      tel: "+54 11 5402-1644"   },
  { nombre: "Estudio Contable Claudio Frustaci",         dir: "Riobamba 212",                      zona: "Balvanera",         tel: "+54 11 4953-1245"   },
  { nombre: "Estudio Contador Rodriguez",                dir: "Ayacucho 457",                      zona: "Balvanera",         tel: "+54 11 4954-3324"   },
  { nombre: "Estudio Contable Del Pino",                 dir: "Av. Entre Ríos 985",                zona: "Congreso",          tel: "+54 11 4304-7723"   },
  { nombre: "Estudio Contable Durand",                   dir: "Av. de Mayo 840",                   zona: "Montserrat",        tel: "+54 11 4345-6612"   },
  { nombre: "Estudio Contable Garriga",                  dir: "Perú 650",                          zona: "Montserrat",        tel: "+54 11 4342-8870"   },
  { nombre: "Estudio Contable Villalba",                 dir: "México 1640",                       zona: "Montserrat",        tel: "+54 11 4383-5581"   },

  // ── CABA · BOEDO / ALMAGRO / SAN CRISTÓBAL ───────────────
  { nombre: "RGA Estudio Contable",                      dir: "Av. Jujuy 573",                     zona: "Boedo",             tel: "+54 11 2394-2199"   },
  { nombre: "Estudio COAS",                              dir: "Av. Juan de Garay 3530",            zona: "Boedo",             tel: "+54 11 7853-0009"   },
  { nombre: "Estudio SOARES",                            dir: "Av. Boedo 1021",                    zona: "Boedo",             tel: "+54 11 6415-7335"   },
  { nombre: "Eichenblat Sergio Contador",                dir: "Colombres 1059",                    zona: "Boedo",             tel: "+54 11 5365-8736"   },
  { nombre: "Estudio Contable AF",                       dir: "F. Acuña de Figueroa 1068",         zona: "Almagro",           tel: "+54 11 2400-3448"   },
  { nombre: "Estudio Contable Villafañe",                dir: "Av. Corrientes 4890",               zona: "Almagro",           tel: "+54 11 4863-2276"   },
  { nombre: "Estudio Contable Ibañez",                   dir: "Guardia Vieja 3780",                zona: "Almagro",           tel: "+54 11 4862-4510"   },
  { nombre: "Estudio Contable ETIQO",                    dir: "Av. Independencia 3600",            zona: "San Cristóbal",     tel: "sin teléfono"       },
  { nombre: "Estudio Contable y Asesoría Impositiva",    dir: "Av. San Juan 1450",                 zona: "San Cristóbal",     tel: "+54 11 4945-0709"   },
  { nombre: "Estudio Contable M&R",                      dir: "Av. San Juan 2820",                 zona: "Parque Patricios",  tel: "+54 11 2144-9097"   },
  { nombre: "Estudio Noriega",                           dir: "Combate de los Pozos 1540",         zona: "Constitución",      tel: "+54 11 4305-3391"   },
  { nombre: "Estudio Contable Ocón",                     dir: "Chile 1820",                        zona: "San Telmo",         tel: "+54 11 4307-9923"   },

  // ── CABA · RECOLETA / TRIBUNALES / RETIRO ────────────────
  { nombre: "Estudio Contable Videla",                   dir: "Paraguay 1606",                     zona: "Recoleta",          tel: "+54 11 5217-9936"   },
  { nombre: "MS & Asociados",                            dir: "Av. Gral. Las Heras 2126",          zona: "Recoleta",          tel: "+54 11 4803-9009"   },
  { nombre: "Estudio Contable BG",                       dir: "Junín 969",                         zona: "Recoleta",          tel: "+54 11 6695-1911"   },
  { nombre: "Estudio Contable Martel",                   dir: "Charcas 3260",                      zona: "Recoleta",          tel: "+54 11 5263-5167"   },
  { nombre: "Estudio Contable Garcia Barraco",           dir: "M.T. de Alvear 1942",               zona: "Recoleta",          tel: "+54 11 5833-9175"   },
  { nombre: "Estudio Contable Aquino",                   dir: "Montevideo 1388",                   zona: "Recoleta",          tel: "+54 11 4240-4125"   },
  { nombre: "Estudio Contable Torres & Asoc.",           dir: "Av. Callao 420",                    zona: "Recoleta",          tel: "+54 11 4811-8843"   },
  { nombre: "Estudio Contable Medina",                   dir: "Av. Santa Fe 1340",                 zona: "Retiro",            tel: "+54 11 4815-2260"   },
  { nombre: "Estudio Contable (Ayacucho 1454)",          dir: "Ayacucho 1454",                     zona: "Recoleta",          tel: "+54 9 11 3002-2039" },

  // ── CABA · PALERMO ────────────────────────────────────────
  { nombre: "Estudio Contable MC",                       dir: "Av. Santa Fe 3435",                 zona: "Palermo",           tel: "+54 11 4840-7160"   },
  { nombre: "Estudio Contable – Tus Finanzas",           dir: "Paraguay 5600",                     zona: "Palermo",           tel: "+54 11 5655-3518"   },
  { nombre: "Estudio Venere",                            dir: "Paunero 2750",                      zona: "Palermo",           tel: "+54 11 4806-8415"   },
  { nombre: "MNS Estudio Contable",                      dir: "Sánchez de Bustamante 2010",        zona: "Palermo",           tel: "+54 9 11 2723-3716" },
  { nombre: "GFPA Consultores",                          dir: "Charcas 3036",                      zona: "Palermo",           tel: "+54 11 2760-8568"   },

  // ── CABA · CABALLITO ──────────────────────────────────────
  { nombre: "Estudio Contable Morrongiello",             dir: "Hidalgo 334",                       zona: "Caballito",         tel: "+54 11 6837-9573"   },
  { nombre: "Estudio Contable Greco",                    dir: "Felipe Vallese 312",                zona: "Caballito",         tel: "+54 11 3680-2121"   },
  { nombre: "Estudio Contable Varela",                   dir: "Bogotá 759",                        zona: "Caballito",         tel: "+54 11 5698-1326"   },
  { nombre: "Andrea Pasquini Contadora",                 dir: "Doblas 330",                        zona: "Caballito",         tel: "+54 11 6650-7784"   },
  { nombre: "Estudio Contable Castellanos",              dir: "Av. Rivadavia 5620",                zona: "Caballito",         tel: "+54 11 4902-5587"   },
  { nombre: "Estudio Contable Hurtado",                  dir: "Av. Rivadavia 6120",                zona: "Caballito",         tel: "+54 11 4905-7792"   },
  { nombre: "Estudio Contable Arredondo",                dir: "Pedro Goyena 1240",                 zona: "Caballito",         tel: "+54 11 4906-2214"   },

  // ── CABA · FLORES / VILLA LURO ───────────────────────────
  { nombre: "Dra. Julia V. Varela",                      dir: "Av. Rivadavia 6817",                zona: "Flores",            tel: "+54 11 4350-4907"   },
  { nombre: "Estudio Contable GP",                       dir: "Av. J.B. Alberdi 833",              zona: "Flores",            tel: "+54 11 4238-1529"   },
  { nombre: "Estudio Contable Jablonka",                 dir: "Av. Carabobo 136",                  zona: "Flores",            tel: "+54 11 4633-3956"   },
  { nombre: "Estudio Contable Gestión Sinérgica",        dir: "Av. Carabobo 455",                  zona: "Flores",            tel: "+54 9 11 3242-8040" },
  { nombre: "Estudio Contable Ribeiro",                  dir: "Av. Eva Perón 2543",                zona: "Flores",            tel: "+54 11 2054-7594"   },
  { nombre: "Estudio Caliri – Contadores Públicos",      dir: "Av. Rivadavia 6351",                zona: "Flores",            tel: "+54 11 4064-2979"   },
  { nombre: "Estudio Contable F&S",                      dir: "Laguna 1263",                       zona: "Villa Luro",        tel: "+54 11 2533-0108"   },

  // ── CABA · VILLA DEL PARQUE / VILLA DEVOTO ───────────────
  { nombre: "Estudio Contable Digital",                  dir: "Guayaquil 134",                     zona: "Villa del Parque",  tel: "+54 11 2845-0195"   },
  { nombre: "Estudio Contable DLC",                      dir: "Cervantes 1134",                    zona: "Villa del Parque",  tel: "+54 11 5693-5638"   },
  { nombre: "Estudio Contable Frachia",                  dir: "C.A. López 2426",                   zona: "Villa del Parque",  tel: "+54 11 3372-4759"   },
  { nombre: "Estudio Contable AGML",                     dir: "Bolivia 4900",                      zona: "Villa del Parque",  tel: "+54 11 6802-1497"   },
  { nombre: "Estudio Contable Olivares",                 dir: "Av. Gaona 3200",                    zona: "Villa del Parque",  tel: "+54 11 4522-8814"   },
  { nombre: "Estudio Contable Noceti",                   dir: "Av. Nazca 1850",                    zona: "Villa del Parque",  tel: "+54 11 4503-1980"   },
  { nombre: "ELP Estudio Contable",                      dir: "Cuenca 2243",                       zona: "Villa del Parque",  tel: "+54 11 3849-6601"   },
  { nombre: "CG Contadores",                             dir: "Pedro Lozano 3175 1°",              zona: "Villa del Parque",  tel: "+54 11 3119-2533"   },
  { nombre: "Estudio Contable & Jurídico",               dir: "José Pedro Varela 4451",            zona: "Villa Devoto",      tel: "+54 11 2251-6545"   },
  { nombre: "Estudio Contable Luna y Asociados",         dir: "Tinogasta 5120",                    zona: "Villa Devoto",      tel: "+54 11 3778-5271"   },
  { nombre: "CCA Estudio Contable",                      dir: "Desaguadero 3252 3C",               zona: "Villa Devoto",      tel: "+54 11 6893-0541"   },
  { nombre: "Estudio Contable LYK",                      dir: "Carlos Antonio López 4348 3C",      zona: "Villa Devoto",      tel: "+54 11 5800-5191"   },
  { nombre: "Estudio Constantin & Asociados",            dir: "Av. S. Martín 6528 4to Piso A",     zona: "Villa del Parque",  tel: "+54 11 7660-6528"   },

  // ── CABA · VILLA URQUIZA ──────────────────────────────────
  { nombre: "Estudio Contable DATA",                     dir: "Altolaguirre 2740",                 zona: "Villa Urquiza",     tel: "+54 11 3499-2110"   },
  { nombre: "Estudio Contable Tesa",                     dir: "Bauness 2170",                      zona: "Villa Urquiza",     tel: "+54 11 6115-4859"   },
  { nombre: "Estudio Contable Mauas, Quiñonez",          dir: "Monroe 5372",                       zona: "Villa Urquiza",     tel: "+54 11 7017-3309"   },
  { nombre: "Estudio Contable CIMA",                     dir: "Ceretti 2002",                      zona: "Villa Urquiza",     tel: "+54 11 3018-0632"   },
  { nombre: "Grupo Profesional Contable",                dir: "Pacheco 2542",                      zona: "Villa Urquiza",     tel: "+54 11 6669-1178"   },
  { nombre: "Estudio Contable First",                    dir: "Juramento 5347",                    zona: "Villa Urquiza",     tel: "+54 11 4068-2049"   },
  { nombre: "Estudio Contable A&G",                      dir: "Av. Triunvirato y Congreso",        zona: "Villa Urquiza",     tel: "+54 9 11 6053-3119" },
  { nombre: "LM Soluciones Contables",                   dir: "Monroe 5731",                       zona: "Villa Urquiza",     tel: "+54 11 6140-7748"   },
  { nombre: "SGR Estudio Contable",                      dir: "Luis Burela 2148",                  zona: "Villa Urquiza",     tel: "+54 11 6357-3474"   },
  { nombre: "Estudio Scarone",                           dir: "Miller 2180",                       zona: "Villa Urquiza",     tel: "+54 11 4035-6698"   },
  { nombre: "Estudio Contable Soria",                    dir: "Av. Triunvirato 4560",              zona: "Villa Urquiza",     tel: "+54 11 4525-7731"   },
  { nombre: "Estudio Contable Rigoni",                   dir: "Bauness 2800",                      zona: "Villa Urquiza",     tel: "+54 11 4527-3385"   },
  { nombre: "Estudio Contable Pereira",                  dir: "Monroe 4890",                       zona: "Villa Urquiza",     tel: "+54 11 4520-0413"   },
  { nombre: "Estudio Contable Giordano",                 dir: "Av. de los Incas 4212",             zona: "Villa Urquiza",     tel: "+54 11 4553-1178"   },

  // ── CABA · BELGRANO / NÚÑEZ / COLEGIALES ─────────────────
  { nombre: "Estudio Contable Sanseau",                  dir: "Crisólogo Larralde 1429",           zona: "Belgrano",          tel: "+54 9 11 2521-1831" },
  { nombre: "Asesorías Contables Crámer",                dir: "Av. Crámer 2762",                   zona: "Belgrano",          tel: "+54 11 3282-6985"   },
  { nombre: "Consultora Stivala",                        dir: "La Pampa 1586",                     zona: "Belgrano",          tel: "+54 9 11 5151-7079" },
  { nombre: "Estudio Contable Brenner",                  dir: "Mendoza 2936 Piso 3",               zona: "Belgrano",          tel: "+54 11 4917-7230"   },
  { nombre: "Estudio GFA",                               dir: "Av. del Libertador 5930",           zona: "Belgrano",          tel: "+54 11 5352-7673"   },
  { nombre: "Estudio Contable Impositivo",               dir: "3 de Febrero 1841",                 zona: "Belgrano",          tel: "+54 9 11 3323-9609" },
  { nombre: "La Vista Casal",                            dir: "Av. Juramento 1475",                zona: "Belgrano",          tel: "+54 11 3987-8266"   },
  { nombre: "Estudio Contable ECG",                      dir: "Av. del Libertador 6550",           zona: "Belgrano",          tel: "+54 9 11 2506-0295" },
  { nombre: "Estudio Contable Moreno & Asociados",       dir: "Cabildo 4128",                      zona: "Belgrano",          tel: "+54 11 4785-2960"   },
  { nombre: "Estudio Contable Lutenberg",                dir: "Av. del Libertador 8620",           zona: "Núñez",             tel: "+54 9 11 6467-9928" },
  { nombre: "Estudio Contable del Amo",                  dir: "Manuel Ugarte 2187 1° Piso",        zona: "Colegiales",        tel: "+54 11 4788-2865"   },

  // ── CABA · LINIERS / MATADEROS / DEVOTO ──────────────────
  { nombre: "Estudio Contable Roldán & Asociados",       dir: "Miranda 4765 Piso 9 B",             zona: "Liniers",           tel: "+54 11 6800-7249"   },
  { nombre: "Estudio Contable Santiago",                 dir: "Lascano 6170",                      zona: "Mataderos",         tel: "+54 11 4046-7189"   },
  { nombre: "Tu Contador Público",                       dir: "Cnel. R.L. Falcón 6835",            zona: "Mataderos",         tel: "+54 11 4643-9144"   },

  // ── CABA · PUERTO MADERO ─────────────────────────────────
  { nombre: "Estudio Bralo y Asoc.",                     dir: "Av. Alicia Moreau de Justo 1150 3°",zona: "Puerto Madero",     tel: "+54 11 5353-4861"   },

  // ── GBA NORTE · SAN ISIDRO / MARTÍNEZ ────────────────────
  { nombre: "Estudio Contable Patricia R. Díaz & Asoc.", dir: "Martin y Omar 129 Piso 4, San Isidro",zona: "San Isidro",     tel: "+54 9 11 6405-8667" },
  { nombre: "Estudio Contable B&oko",                    dir: "Laprida, San Isidro",               zona: "San Isidro",        tel: "+54 9 11 6861-6903" },
  { nombre: "BONINO – Estudio Contable",                 dir: "Yapeyú 1471, Martínez",             zona: "Martínez",          tel: "+54 9 11 5377-1124" },
  { nombre: "Estudio Madero & Asoc.",                    dir: "25 de Mayo 574, San Isidro",        zona: "San Isidro",        tel: "+54 11 4747-9494"   },
  { nombre: "Contador Estudio Contable NC",              dir: "Castelli 2581, Martínez",           zona: "Martínez",          tel: "+54 11 3069-3149"   },
  { nombre: "Estudio Contable Fieg y Asociados",         dir: "Gral. Manuel Belgrano 384, San Isidro",zona: "San Isidro",    tel: "+54 11 4747-3134"   },
  { nombre: "Estudio Contable e Impositivo Tasín",       dir: "Cnel. Cetz 470 Piso 1, San Isidro",zona: "San Isidro",        tel: "+54 11 4765-1348"   },
  { nombre: "Estudio Varese",                            dir: "Av. Sta Fe 1878, Martínez",         zona: "Martínez",          tel: "+54 11 4793-2001"   },
  { nombre: "Estudio Integral Contable Vecchio",         dir: "Int. Tomkinson 2910, San Isidro",   zona: "San Isidro",        tel: "+54 11 3032-6229"   },

  // ── GBA NORTE · TIGRE / VICTORIA / BOULOGNE ──────────────
  { nombre: "Estudio Tigre Contadores",                  dir: "Olazábal 516, Troncos del Talar",   zona: "Tigre",             tel: "+54 9 11 6641-7073" },
  { nombre: "Estudio Contable MD",                       dir: "Solís 1, Tigre",                    zona: "Tigre",             tel: "+54 11 6860-5222"   },
  { nombre: "Galcontable",                               dir: "Paso 3, Tigre",                     zona: "Tigre",             tel: "+54 11 6792-9234"   },
  { nombre: "Estudio RF",                                dir: "Butteler 685 A, Tigre",             zona: "Tigre",             tel: "+54 11 3932-5943"   },
  { nombre: "Estudio Contable e Imp. Dominguez",         dir: "Av. Pres. Perón 2302, Victoria",    zona: "Victoria",          tel: "+54 9 11 3058-0334" },
  { nombre: "Contadoras Públicas Puebla y Friedrich",    dir: "Av. Dardo Rocha 699, Tigre",        zona: "Tigre",             tel: "+54 11 5479-7833"   },
  { nombre: "Estudio Contable Tigre (Boulogne)",         dir: "Blandengues 163, Boulogne",         zona: "Boulogne",          tel: "+54 11 6641-7073"   },
  { nombre: "Contador IB",                               dir: "Av. del Golf 2100, Tigre",          zona: "Tigre",             tel: "+54 11 3121-4776"   },
  { nombre: "Estudio Contable SyS – Tigre",              dir: "Alfredo Palacios 331, Troncos",     zona: "Tigre",             tel: "+54 11 3655-7501"   },

  // ── GBA SUR · LANÚS / AVELLANEDA ─────────────────────────
  { nombre: "Estudio Contable AL",                       dir: "Marco Avellaneda 3115, Lanús",      zona: "Lanús",             tel: "+54 11 5117-2079"   },
  { nombre: "Estudio Contable Scholiadis",               dir: "Humberto Primo 2324, Lanús",        zona: "Lanús",             tel: "+54 11 6056-7328"   },
  { nombre: "Estudio Contable Belloli Y Asociados",      dir: "Enrique Fernández 354, Lanús",      zona: "Lanús",             tel: "+54 11 6504-7455"   },
  { nombre: "Estudio Contable Reynoso y Asociados",      dir: "Ituzaingó 1452, Lanús",             zona: "Lanús",             tel: "+54 11 4241-3470"   },
  { nombre: "Estudio Fulleri",                           dir: "Monseñor Piaggio 198 1°E, Avellaneda",zona: "Avellaneda",      tel: "+54 11 4222-3559"   },
  { nombre: "Estudio Contable Dr. Server",               dir: "Av. Manuel Belgrano 595, Avellaneda",zona: "Avellaneda",       tel: "+54 9 11 5004-5528" },
  { nombre: "Estudio Colucci & Asociados",               dir: "Ing. Marconi 570, Avellaneda",      zona: "Avellaneda",        tel: "+54 11 2058-7865"   },
  { nombre: "CEMM Martini y Asociados",                  dir: "Mariano Acosta 137 Piso 8, Avellaneda",zona: "Avellaneda",     tel: "+54 11 4201-6787"   },

  // ── GBA SUR · LOMAS / BANFIELD / QUILMES / BERAZATEGUI ───
  { nombre: "Estudio Contable Aurensanz Forgione",       dir: "Valentín Vergara 1543, Banfield",   zona: "Banfield",          tel: "+54 9 11 6214-3238" },
  { nombre: "HO Estudio Contable",                       dir: "Gral. Arenales 2000, Banfield",     zona: "Banfield",          tel: "+54 11 3574-5898"   },
  { nombre: "Estudio Contable Bermúdez",                 dir: "French 331, Banfield",              zona: "Banfield",          tel: "+54 11 4242-0118"   },
  { nombre: "Estudio Contable Ruster",                   dir: "Gral. C.M. de Alvear 1474, Banfield",zona: "Banfield",         tel: "+54 11 5711-1421"   },
  { nombre: "Estudio Contable Mondejar",                 dir: "Pedernera 89 Of. A, Lomas de Zamora",zona: "Lomas de Zamora",  tel: "+54 11 6620-1346"   },
  { nombre: "Estudio Contadoreo",                        dir: "Juan José Castelli 917, Lomas de Zamora",zona: "Lomas de Zamora",tel: "+54 11 2387-6305" },
  { nombre: "Estudio Contable Integral Páez",            dir: "Andrés Baranda 1572, Quilmes",      zona: "Quilmes",           tel: "+54 11 2870-6896"   },
  { nombre: "Estudio Contable Campillo Abel y Asoc.",    dir: "Calle 13 5243, Berazategui",        zona: "Berazategui",       tel: "+54 9 11 4045-6735" },
  { nombre: "Estudio Contable Sikora",                   dir: "C. 149 1351, Berazategui",          zona: "Berazategui",       tel: "+54 11 3673-0443"   },
  { nombre: "Pacheco & Cargnello Estudio Contable",      dir: "Calles 137 y 13, Berazategui",      zona: "Berazategui",       tel: "+54 11 6516-2564"   },
  { nombre: "Harriague Estudio Contable",                dir: "C. 12 N°4629, Berazategui",         zona: "Berazategui",       tel: "+54 9 11 4165-5416" },
  { nombre: "Estudio Contable Dr. Daniel García",        dir: "Av. 14 4247, Berazategui",          zona: "Berazategui",       tel: "+54 11 4249-5107"   },

  // ── GBA OESTE · MORÓN / HAEDO / ITUZAINGÓ / CASTELAR ────
  { nombre: "Dominguez & Asoc. – Estudio Contable",      dir: "9 de Julio 346, Morón",             zona: "Morón",             tel: "+54 11 2733-9227"   },
  { nombre: "Estudio Contable Morgado & Asoc.",          dir: "Brown 523, Morón",                  zona: "Morón",             tel: "+54 11 5096-1682"   },
  { nombre: "Estudio Contable Integral MO",              dir: "Manuel Láinez 1657, Haedo",         zona: "Haedo",             tel: "+54 11 6597-5484"   },
  { nombre: "Estudio Contable de la Hoz",                dir: "Vuelta de Obligado 2231, Haedo",    zona: "Haedo",             tel: "+54 11 3378-8301"   },
  { nombre: "CMadviser Estudio Contable Haedo",          dir: "Monseñor de Andrea 301, Haedo",     zona: "Haedo",             tel: "+54 44432881"       },
  { nombre: "Estudio Contable Ontivero",                 dir: "Gral. las Heras 464, Ituzaingó",    zona: "Ituzaingó",         tel: "+54 11 5880-9679"   },
  { nombre: "Estudio Contable G Grosso y Asoc.",         dir: "Gral. Villegas 2565, Ituzaingó",    zona: "Ituzaingó",         tel: "+54 11 6703-5342"   },
  { nombre: "Estudio Contable AV",                       dir: "Ombú 205, Ituzaingó",               zona: "Ituzaingó",         tel: "+54 11 5317-7310"   },
  { nombre: "Estudio contable Massare Muñoz",            dir: "Cnel. Hortiguera 1844, Ituzaingó",  zona: "Ituzaingó",         tel: "+54 11 2223-3915"   },
  { nombre: "Estudio Dr Ángel Cuello",                   dir: "De los Reseros 1871, Ituzaingó",    zona: "Ituzaingó",         tel: "+54 11 2470-1416"   },
  { nombre: "Bookkeeping Alba",                          dir: "Álvarez Jonte 2095, Castelar",      zona: "Castelar",          tel: "+54 11 3591-1445"   },
  { nombre: "CVL Consultora Estudio Contable",           dir: "Leandro N. Alem 2684, Castelar",    zona: "Castelar",          tel: "+54 11 4039-8755"   },
  { nombre: "CY Estudio Contable",                       dir: "Nicolás Avellaneda 935, Castelar",  zona: "Castelar",          tel: "sin teléfono"       },
  { nombre: "Estudio AW",                                dir: "Gdor. Inocencio Arias 3313, Castelar",zona: "Castelar",        tel: "+54 11 4623-8583"   },

  // ── GBA SUR · LA PLATA ────────────────────────────────────
  { nombre: "Estudio Garcia – Contadores La Plata",      dir: "Calle 56 1327, La Plata",           zona: "La Plata",          tel: "+54 221 577-6777"   },
  { nombre: "SUMMA Estudio Contable",                    dir: "C. 11 1179, La Plata",              zona: "La Plata",          tel: "+54 221 507-7690"   },
  { nombre: "Estudio Fernandez Giachella y Socios",      dir: "C. 21 1016, La Plata",              zona: "La Plata",          tel: "+54 221 678-2793"   },
  { nombre: "Contadores Bourdin, Mendy & Asociados",     dir: "C. 12 779 Depto 8D, La Plata",      zona: "La Plata",          tel: "+54 221 201-5428"   },
  { nombre: "Estudio Sastre – Contadores",               dir: "Diag. 75 149, La Plata",            zona: "La Plata",          tel: "+54 221 408-4398"   },
  { nombre: "Estudio Contable Libran",                   dir: "Calle 39 778 1° A, La Plata",       zona: "La Plata",          tel: "+54 221 421-6220"   },
  { nombre: "Estudio Contable Neira",                    dir: "C. 75 274, La Plata",               zona: "La Plata",          tel: "+54 221 576-6420"   },
  { nombre: "Estudio Contable K&S",                      dir: "C. 8 n°835, La Plata",              zona: "La Plata",          tel: "+54 11 3252-0732"   },
  { nombre: "Estudio Contable Jimena Garcia",            dir: "C. 48 874, La Plata",               zona: "La Plata",          tel: "+54 221 420-4198"   },
  { nombre: "Estudio en Línea",                          dir: "C. 59 381, La Plata",               zona: "La Plata",          tel: "+54 221 694-1154"   },

  // ── GBA OESTE · SAN JUSTO ────────────────────────────────
  { nombre: "Estudio Contable B&S",                      dir: "Florencio Varela 3966, San Justo",  zona: "San Justo",         tel: "+54 11 2699-7663"   },
  { nombre: "Estudio Contable Formaggio",                dir: "Perú 2234, San Justo",              zona: "San Justo",         tel: "+54 11 2368-5844"   },
  { nombre: "Estudio Contable NAQ",                      dir: "Virrey Cisneros 2628, San Justo",   zona: "San Justo",         tel: "+54 11 6407-4152"   },
  { nombre: "Estudio Contable Travaglini",               dir: "Av. Brig. Rosas 4855, San Justo",   zona: "San Justo",         tel: "+54 11 4441-7178"   },
  { nombre: "GB Estudio Contable",                       dir: "Entre Ríos 2969 3°C, San Justo",    zona: "San Justo",         tel: "+54 11 4484-7175"   },
  { nombre: "Estudio Impositivo Amendolara",             dir: "Pres. Perón 3094, San Justo",       zona: "San Justo",         tel: "+54 11 4482-7104"   },
];

module.exports = PROSPECTOS_200;
