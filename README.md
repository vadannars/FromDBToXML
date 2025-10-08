
# Lånestatus API-klient

Detta PHP-baserade webb-API fungerar som en brygga mellan Libris och ett biblioteks Sierra API. Tjänsten gör det möjligt för användare (t.ex. Libris eller andra bibliotekssystem) att kontrollera status på media genom att ange identifierare som ISBN, Libris-ID, ISSN eller ONR. Svaret returneras som en XML-fil med status för varje hittat exemplar.

**Typiskt användningsfall:**
Ett system skickar en förfrågan med en identifierare och får tillbaka ett XML-svar som visar om mediet är tillgängligt, utlånat, reserverat osv.

Den typ av API som ansluts mot är den här: https://sandbox.iii.com/iii/sierra-api/swagger/index.html

---

## Funktioner

- Accepterar flera typer av identifierare (ISBN, Libris-ID, ISSN, ONR)
- Kommunicerar säkert med Sierra API
- Returnerar resultat i ett standardiserat XML-format
- Konfigureras via miljövariabler eller `.env`-fil
- Robust felhantering och loggning
- Enkel att driftsätta lokalt eller i molnet

---



## Installation och kom igång

### 1. Klona projektet
```bash
git clone [DIN_REPOSITORY_URL]
cd [PROJEKTNAMN]
```

### 2. Installera PHP och Composer
- PHP 8.2 eller senare krävs.
- Composer installeras enligt [officiell guide](https://getcomposer.org/download/).

### 3. Installera beroenden
```bash
composer install
```

### 4. Sätt upp miljövariabler
- **Rekommenderat:** Sätt miljövariabler direkt i serverns miljö (t.ex. via webbhotellspanel, systemd, Docker eller liknande).
- **Alternativ:** Kopiera `.env.example` till `.env` och fyll i värdena. Se konfigurationsreferensen längre ned för fullständig lista.
- Exempel på viktiga variabler:
  ```
  API_KEY=din_api_nyckel
  API_SECRET=din_api_hemlighet
  API_BASE_URL=https://ditt-bibliotek.se/iii/sierra-api/
  ALLOWED_ORIGINS=https://libris.kb.se,https://din-domän.se
  ACTIVE=true
  LOG_LEVEL=info
  LOG_DESTINATION=/sökväg/till/loggfil.log
  ```
Se `.env.example` för alla tillgängliga alternativ och beskrivningar.
> **Obs!** I produktion kan du sätta dessa som miljövariabler direkt om din plattform stödjer det (rekommenderas för servertekniker).

### 5. Sätt rättigheter på loggkatalogen
Se till att katalogen för loggar (`logs/` eller den du anger i `LOG_DESTINATION`) är skrivbar för webbserverns användare.
Exempel (Linux):
```bash
mkdir -p logs
chown www-data:www-data logs
chmod 770 logs
```

### 6. Konfigurera webbservern
- Peka webbserverns dokumentrot mot projektets `public/`-katalog.
- Exempel för Apache (se även `apache.conf` i projektet):
  ```
  DocumentRoot /sökväg/till/projektet/public
  DirectoryIndex loanstatus.php index.php index.html
  <Directory "/sökväg/till/projektet/public">
		Options Indexes FollowSymLinks
		AllowOverride All
		Require all granted
  </Directory>
  ```
- För Nginx eller annan server: se till att PHP-filer i `public/` kan exekveras.

### 7. Starta tjänsten
```bash
php -S 0.0.0.0:8080 -t public
```
API:et är nu tillgängligt på [http://localhost:8080](http://localhost:8080).

### 8. Testa installationen
Besök API:et via webbläsare eller med `curl`:
```bash
curl 'https://din-server.se/loanstatus.php?isbn=9789177754657'
```
Kontrollera att du får ett XML-svar och att inga fel syns i loggarna.

### 9. Kör tester
```bash
./vendor/bin/phpunit
```

---

## Så fungerar det

1. **Förfrågan:**  
	 En klient skickar en HTTP-förfrågan till `public/loanstatus.php` med en eller flera identifierare som URL-parametrar (t.ex. `?isbn=9781234567890`).

2. **Bearbetning:**  
	 - Kontrollern (`src/LoanStatusController.php`) validerar indata och styr arbetsflödet.
	 - Sierra API-klienten (`src/SierraApiClient.php`) autentiserar och söker i Sierra API efter matchande exemplar.
	 - XML-generatorn (`src/XmlGenerator.php`) formaterar resultatet till ett XML-svar.

3. **Svar:**  
	 Tjänsten returnerar en XML-fil med status för varje hittat exemplar.

---


## Konfigurationsreferens

All konfiguration sker via miljövariabler eller en `.env`-fil. om det är oklart hur de ska anges så försök följa hur de har skrivits in i .env.example. Generellt kan sägas att värden som hanteras som text i logiken läggs in inom ciatationstecken ("textvärde") men andra typer av värden, som integers (siffror) eller booleans (true/false) läggs in utan. Koden ska vara någorlunda robust och kontrollera alla värden så det kan gå även om de läggs in fel, men försök att hålla formen till den som ligger i .env.example. Nedan ligger flera värden med andra typer av ciationstecken, men det är enbart för läsbarhet.

### Grundläggande inställningar
- `API_KEY` / `API_SECRET`: Inloggningsuppgifter för Sierra API. Biblioteket behöver skapa en specifik api-användare vars hemlighet och nyckel används för funktionen.
- `API_BASE_URL`: Bas-URL till Sierra API (aktuell vid publicering: `"https://gotlib.goteborg.se/iii/sierra-api/v6"`)
- `ALLOWED_ORIGINS`: Komma-separerad lista över tillåtna domäner för CORS. Det här är alltså domäner som tillåts ansluta mot tjänsten. I sak behövs bara `"https://libris.kb.se"` men fler kan läggas till för testning eller framtida ändringar.
- `ACTIVE`: Sätt till `true` för att aktivera tjänsten, `false` för att inaktivera. Av/På för appen om ni vill stoppa den men inte stänga ner den på servern.
- `LOG_LEVEL`: Loggningsnivå (se nedan)
- `LOG_DESTINATION`: Sökväg till loggfil (eller lämna tomt för standard)

### Loggning
`LOG_LEVEL` styr hur detaljerad loggningen blir. Följande nivåer stöds (från mest till minst detaljerad):

- `debug` – Allt loggas (mest detaljerad, för felsökning)
- `info` – Viktig information om normal drift
- `notice` – Notiser om ovanliga men ej kritiska händelser
- `warning` – Varningar om potentiella problem
- `error` – Fel som kräver åtgärd
- `critical` – Kritiska fel som påverkar funktionalitet
- `alert` – Allvarliga fel som kräver omedelbar åtgärd
- `emergency` – Systemet är obrukbart

Exempel:
```
LOG_LEVEL=info
```

Om `LOG_DESTINATION` inte är skrivbar skickas loggar automatiskt till `php://stderr` (standard i molnmiljöer).

### API-endpoints
- `TOKEN_ENDPOINT`:
	Adressen för autentisering mot Sierras API. Finns som variabel om adressen skulle ändras i framtiden.
- `QUERY_ENDPOINT`:
	Adressen för att skicka JSON-querys för lista med bibliografiska poster.
- `ITEMS_ENDPOINT`:
	Adressen för hämtning av lista med exemplar från den tidigare funna bibliografiska posten.

### Query-parametrar
- `QUERY_OFFSET`:
	Styr var i index sökningen börjar. obligatoriskt värde för att skicka querys. Ska sättas till `0`
- `QUERY_LIMIT`:
	Övre gräns för mängden bibliografiska poster som hämtas utifrån queryn. Obligatoriskt för att skicka querys.
    Satt till 10 för att ha en gräns. Tanken är att en specifik bibliografisk post ska hittas. Sätt till `10`
- `QUERY_LIBRIS_ID`:
	Fälttagg och taggvärde för det fält som i Sierra representerar Libris.kb:s identifikationsnummer. Form: "[fälttagg]:[taggvärde]" (inklusive citationstecken). Exempel: `"tag:j"`
- `QUERY_ISBN`:
	Som QUERY_LIBRIS_ID men för ISBN. Exempel: `"tag:i"`
- `QUERY_ISSN`:
	Som de ovan men för ISSN. Exempel: `"marcTag:022"`
- `QUERY_ONR`:
	Som ovan men för ONR, Libris.kb:s äldre identifikationsnummer. Exempel: `"marcTag:035"`

### Item fields
- `ITEM_FIELDS`:
	Kommaseparerad lista med de fält från exemplarsposten som ska hämtas. Istället för att hela posten laddas, så tas bara dessa fält. Går att utöka eller minska om behovet finns vid vidareutveckling.
    Just nu används bara de som anges i exemplet, men det finns placeholders i koden för andra fält och fler taggar i XML-strukturen. För alla tillgängliga fält se https://sandbox.iii.com/iii/sierra-api/swagger/index.html#!/items/Get_an_item_by_record_ID_get_6
    Exempel: `"location,callNumber,status"`
Se `.env.example` för exempelvärden och vid tid för publicering aktuella endpoints och fält-taggar.

> **Tips:** I produktion, sätt dessa som miljövariabler om möjligt för bättre säkerhet och flexibilitet.

---

## Exempel på anrop

```
GET /loanstatus.php?isbn=9789177754657
```

**Svar (XML):**
```xml
<status>
	<channel>
		<description>Exemplarstatus för böcker i Göteborgs biblioteks katalog</description>
		<Item_information>
			<Item>
				<Item_No>1</Item_No>
				<Location>Huvudbiblioteket</Location>
				<Call_No>Hce.3</Call_No>
				<Status>Utlånad</Status>
				<Status_Date>2025-09-30</Status_Date>
				<Status_Date_Description>ÅTER </Status_Date_Description>
				<Loan_Policy></Loan_Policy>
				<UniqueItemId></UniqueItemId>
			</Item>
			<!-- Fler exemplar... -->
		</Item_information>
	</channel>
</status>
```

---

## Felsökning

- **Inget svar / 500-fel:**  
	Kontrollera att alla nödvändiga miljövariabler är satta och giltiga. Se loggar för detaljer.

- **CORS-fel:**  
	Kontrollera att `ALLOWED_ORIGINS` innehåller domänen som gör anropet.

- **Loggfilen skrivs inte:**  
	Kontrollera att `LOG_DESTINATION` är skrivbar, eller lämna tomt för standardloggning.

---

## Utveckling & bidrag

- Koden följer PSR-12 (se `phpcs.xml`)
- Tester finns i katalogen `tests/` och använder PHPUnit
- Loggning sker med Monolog
- HTTP-anrop görs med Guzzle

---

## Säkerhet & uppförandekod

- Se `SECURITY.md` för instruktioner om säkerhetsrapportering.
- Projektet följer [Microsoft Open Source Code of Conduct](https://opensource.microsoft.com/codeofconduct/).

---