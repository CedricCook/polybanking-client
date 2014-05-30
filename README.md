PolyBanking - Client
===========

[PolyBanking](https://github.com/PolyLAN/polybanking) is a django application that allows use of PostFinance's [E-payement](https://www.postfinance.ch/fr/biz/prod/eserv/epay.html?WT.ac=_techshortcut_bizprodeservepayfr) service when running multiple websites.

PolyBanking allows different services, through different configurations to request payment. The system will ask and forward the user to Postfinance, and will report the result and status of a payment back to the website who created the request.

PolyBanking is able to compare PostFinance's reports with its database, export transactions and create various reports from transactions.

This is a PHP client for PolyBanking, essentially a Python to PHP conversion of [Maximilien Cuony](https://github.com/the-glu)'s client.

## License

PolyBanking is distributed with the [BSD](http://opensource.org/licenses/BSD-2-Clause) license. It includes code from Azimut-prod, Copyright (c) 2013 Azimut-Prod (Deployment scripts).

## Authors

This client was made by [Cedric Cook](https://github.com/CedricCook)

PolyBanking has been developed by [Maximilien Cuony](https://github.com/the-glu) and reviewed by [Malik Bougacha](https://github.com/gcmalloc) for the [AGEPoly](http://agepoly.ch) and [PolyLAN](https://polylan.ch).
