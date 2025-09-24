Loan Status API Client
Detta är en PHP-applikation som fungerar som en mellanhand mellan Libris och ett biblioteks-API (Sierra API) för att hämta status på böcker. Applikationen tar emot identifierare (som ISBN eller Libris ID) via URL-parametrar och returnerar en XML-fil med status för de exemplar som hittas.

1. Installationsguide för utvecklare och servertekniker
Följ dessa steg för att installera applikationen lokalt eller på en produktionsserver.

Steg 1: Klona eller ladda ner repository
Klona repositoryt till din server eller lokala miljö:

git clone [DITT_REPOSITORY_URL]
cd [PROJEKTETS_NAMN]

Steg 2: Installera beroenden
Applikationen använder Composer för att hantera beroenden. Se till att Composer är installerat på ditt system och kör sedan:

composer install

Detta kommer att installera alla nödvändiga bibliotek, inklusive Monolog för loggning och Dotenv för att hantera miljövariabler.

Steg 3: Konfiguration (Miljövariabler)
Applikationen läser sina konfigurationsinställningar från miljövariabler. För att köra applikationen lokalt kan du skapa en .env-fil i projektets rotmapp. I en produktionsmiljö (t.ex. på Clever Cloud) konfigureras dessa variabler direkt i plattformens inställningar.

Följande variabler måste vara definierade:

# API-nycklar för autentisering mot Sierra API
API_KEY="[Din API-nyckel här]"
API_SECRET="[Din API-nyckel här]"

# Bas-URL för Sierra API
API_BASE_URL="[https://example.com/iii/sierra-api/](https://example.com/iii/sierra-api/)"

# Ursprung som tillåts att göra anrop mot denna tjänst (CORS)
# Separera flera ursprung med kommatecken, t.ex. "[https://libris.kb.se](https://libris.kb.se),[https://example.com](https://example.com)"
ALLOWED_ORIGINS="[Tillåtet ursprung]"

# Övriga konfigurationsvariabler
ACTIVE=true
LOG_LEVEL=info
LOG_DESTINATION=/path/to/your/log/file.log

Observera: Om LOG_DESTINATION inte är skrivbar kommer loggarna automatiskt att skickas till php://stderr, vilket är standard i molnbaserade miljöer.

Steg 4: Köra applikationen
Denna applikation är en webbtjänst. Du kan köra den med PHP:s inbyggda webbserver för testning:

php -S 0.0.0.0:8080 -t public

Applikationen blir då tillgänglig på http://localhost:8080.

Steg 5: Köra tester
För att verifiera att allt fungerar som det ska, kan du köra enhetstesterna med PHPUnit:

./vendor/bin/phpunit

2. Konfigurationsreferens för administratörer
Det här avsnittet förklarar syftet med varje inställning. Använd det för att förstå vad du konfigurerar på din server.

Autentiserings- och API-inställningar
API_KEY och API_SECRET: Dessa är dina unika nycklar för att få tillgång till bibliotekets API. De fungerar som en kombination av användarnamn och lösenord som verifierar att din applikation har behörighet att hämta data.

API_BASE_URL: Detta är basadressen till bibliotekets API-tjänst. Hela URL-strängen ser ut som: https://[ditt_bibliotek].se/iii/sierra-api/.

TOKEN_ENDPOINT, QUERY_ENDPOINT, ITEMS_ENDPOINT: Dessa definierar de specifika sökvägarna (endpoints) som applikationen använder för att hämta API-åtkomsttoken, söka efter poster och hämta information om exemplar. Om din API-adress skulle ändras, justera dessa variabler därefter.

Sök- och fältkonfiguration
QUERY_OFFSET och QUERY_LIMIT: Dessa variabler används för paginering i API-anropen.

QUERY_OFFSET bestämmer hur många poster som ska hoppas över i sökresultatet (startposition).

QUERY_LIMIT sätter det maximala antalet poster som returneras per anrop.

QUERY_LIBRIS_ID: Definierar Sierra API-sökfältet som används för att matcha ett Libris ID. Värdet tag:j är en specifik sökparameter i Sierra API som kopplar till Libris ID.

QUERY_ISBN: Definierar Sierra API-sökfältet som används för att matcha ett ISBN (International Standard Book Number). Värdet tag:i är en specifik sökparameter i Sierra API som kopplar till ISBN.

QUERY_ISSN: Definierar Sierra API-sökfältet som används för att matcha ett ISSN (International Standard Serial Number). Värdet marcTag:022 är MARC 21-taggen för ISSN.

QUERY_ONR: Definierar Sierra API-sökfältet som används för att matcha ett Order Number eller ett annat systemkontrollnummer. Värdet marcTag:035 är MARC 21-taggen för System Control Number.

ITEM_FIELDS: En kommaseparerad lista över de fält som ska hämtas för varje exemplar (item) från API:et. Standardvärden är location (hyllplats), callNumber (signum) och status (utlåningsstatus). Du kan lägga till eller ta bort fält beroende på dina behov.

Applikations- och logginställningar
ACTIVE: En enkel på/av-knapp för applikationen. Om värdet är true är tjänsten aktiv, om false är den inaktiverad och returnerar ett fel.

LOG_LEVEL och LOG_DESTINATION:

LOG_LEVEL: Bestämmer hur detaljerade loggarna ska vara. Värden kan vara debug (allt), info (viktig information), warning (varningar), error (fel) etc. I en produktionsmiljö rekommenderas info eller warning för att undvika att loggfilerna blir för stora.

LOG_DESTINATION: Bestämmer var loggfilerna ska sparas på servern. Om applikationen körs i en molnmiljö, kan du vanligtvis strunta i den här inställningen eftersom loggarna istället visas i molntjänstens konsol.