git clone [DITT_REPOSITORY_URL]


# Lånestatus API-klient

Detta PHP-baserade webb-API fungerar som en brygga mellan Libris och ett biblioteks Sierra API. Tjänsten gör det möjligt för användare (t.ex. Libris eller andra bibliotekssystem) att kontrollera status på böcker genom att ange identifierare som ISBN, Libris-ID, ISSN eller ONR. Svaret returneras som en XML-fil med status för varje hittat exemplar.

**Typiskt användningsfall:**
Ett system skickar en förfrågan med en bokidentifierare och får tillbaka ett XML-svar som visar om boken är tillgänglig, utlånad, reserverad osv.

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
- **Alternativ:** Kopiera `.env.example` till `.env` och fyll i värdena.
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

All konfiguration sker nu via miljövariabler eller en `.env`-fil. Den gamla `config/config.json` används inte längre.

**Viktiga inställningar:**
- `API_KEY` / `API_SECRET`: Inloggningsuppgifter för Sierra API
- `API_BASE_URL`: Bas-URL till Sierra API (t.ex. `https://ditt-bibliotek.se/iii/sierra-api/`)
- `ALLOWED_ORIGINS`: Komma-separerad lista över tillåtna domäner för CORS
- `ACTIVE`: Sätt till `true` för att aktivera tjänsten, `false` för att inaktivera
- `LOG_LEVEL`: Loggningsnivå (`debug`, `info`, `warning`, `error`)
- `LOG_DESTINATION`: Sökväg till loggfil (eller lämna tomt för standard)
- `TOKEN_ENDPOINT`, `QUERY_ENDPOINT`, `ITEMS_ENDPOINT`: Avancerat, för anpassade API-endpoints
- `QUERY_OFFSET`, `QUERY_LIMIT`, `QUERY_LIBRIS_ID`, `QUERY_ISBN`, `QUERY_ISSN`, `QUERY_ONR`, `ITEM_FIELDS`: Avancerat, för att styra API-frågor och fältmappning

Se `.env.example` för alla tillgängliga alternativ och beskrivningar.

> **Tips:** I produktion, sätt dessa som miljövariabler om möjligt för bättre säkerhet och flexibilitet.

> **Obs!** Om `LOG_DESTINATION` inte är skrivbar skickas loggar automatiskt till `php://stderr` (standard i molnmiljöer).

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

## Mer läsning

- Se `.github/copilot-instructions.md` för teknisk arkitektur och AI-agentinstruktioner.
- Se `projectinfo.txt` för sammanfattning av konfiguration och driftsättning.

---