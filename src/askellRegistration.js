class AskellRegistration extends React.Component {
	constructor(props) {
		let currentYear = new Date().getFullYear()
		super(props);
		this.state = {
			blockId: _.uniqueId('askell-registration-block-'),
			currentYear: currentYear,
			firstName: '',
			lastName: '',
			emailAddress: '',
			username: '',
			password: '',
			termsAccepted: false,
			cardHolderName: '',
			cardNumber: '',
			cardNumberSpaced: '',
			cardExpiryMonth: '1',
			cardExpiryYear: currentYear,
			cardIssuer: '',
			cardIssuerName: '',
			cardSecurityCode: ''
		};
		this.onChangeFirstName = this.onChangeFirstName.bind(this);
		this.onChangeLastName = this.onChangeLastName.bind(this);
		this.onChangeEmailAddress = this.onChangeEmailAddress.bind(this);
		this.onChangeUsername = this.onChangeUsername.bind(this);
		this.onChangePassword = this.onChangePassword.bind(this);
		this.onChangeTermsAccepted = this.onChangeTermsAccepted.bind(this);
		this.onClickUserInformationNextStep = this.onClickUserInformationNextStep.bind(this);
		this.onChangeCardHolderName = this.onChangeCardHolderName.bind(this);
		this.onChangeCardNumber = this.onChangeCardNumber.bind(this);
		this.onChangeCardExpiryMonth = this.onChangeCardExpiryMonth.bind(this);
		this.onChangeCardExpiryYear = this.onChangeCardExpiryYear.bind(this);
		this.onChangeCardSecurityCode = this.onChangeCardSecurityCode.bind(this);
	}

	onChangeFirstName(event) {
		this.setState( { firstName: event.target.value } );
	}

	onChangeLastName(event) {
		this.setState( { lastName: event.target.value } );
	}

	onChangeEmailAddress(event) {
		this.setState( { emailAddress: event.target.value } );
	}

	onChangeUsername(event) {
		let sanitisedUsername = event.target.value.replace(/([^a-z|0-9])/g, '');
		this.setState( { username: sanitisedUsername } );
	}

	onChangePassword(event) {
		this.setState( { password: event.target.value } );
	}

	onChangeTermsAccepted(event) {
		this.setState( { termsAccepted: event.target.checked } );
	}

	onClickUserInformationNextStep(event) {
		event.preventDefault();
		console.log( this.state );
	}

	onChangeCardHolderName(event) {
		this.setState( { cardHolderName: event.target.value } );
	}

	onChangeCardNumber(event) {
		// Sanitise the card number, removing all non-numeric characters. This
		// is the value that is then sent to the API.
		let cleanCardNumber = event.target.value.replace(/[^\d.-]+/g, '');

		// Slices the card number into 4 space-separated sections, which is more
		// human readable. This is the value displayed in the form field.
		let spacedCardNumber = [
			cleanCardNumber.slice(0,4),
			cleanCardNumber.slice(4,8),
			cleanCardNumber.slice(8,12),
			cleanCardNumber.slice(12,19)
		].filter(portion => portion !== '').join(' ');;

		this.setState({
			cardNumber: cleanCardNumber,
			cardNumberSpaced: spacedCardNumber
		});

		this.displayCardIssuer(cleanCardNumber);
	}

	onChangeCardExpiryMonth(event) {
		this.setState( { cardExpiryMonth: event.target.value } );
	}

	onChangeCardExpiryYear(event) {
		this.setState( { cardExpiryYear: event.target.value } );
	}

	onChangeCardSecurityCode(event) {
		let cleanCode = event.target.value.replace(/[^\d.-]+/g, '').slice(0,4);
		this.setState( { cardSecurityCode: cleanCode } );
	}

	cardIsAmericanExpress(cardNumber) {
		if ((cardNumber.startsWith(34) || cardNumber.startsWith(37))) {
			return true;
		}

		return false;
	}

	cardIsDinersClub(cardNumber) {
		if (cardNumber.startsWith(36) || cardNumber.startsWith(54)) {
			return true;
		}
		return false;
	}

	cardIsDiscover(cardNumber) {
		if ( cardNumber.startsWith('6011') ) {
			return true
		}

		if ( cardNumber.startsWith('65') ) {
			return true
		}

		if (
			parseInt(cardNumber.slice(0, 3)) >= 644 &&
			parseInt(cardNumber.slice(0, 3) <= 649)
		) {
			return true
		}

		if (
			parseInt(cardNumber.slice(0, 6)) >= 622126 &&
			parseInt(cardNumber.slice(0, 6) <= 622925)
		) {
			return true
		}

		return false
	}

	cardIsMaestro(cardNumber) {
		const INNs = [
			6759, 676770, 676774, 5018, 5020, 5038, 5893, 6304, 6759, 6761,
			6762, 6763,
		];

		let i = 0;

		while (i < INNs.length) {
			if (cardNumber.startsWith(INNs[i])) {
				return true;
			}
			i++;
		}

		return false;
	}

	cardIsMasterCard(cardNumber) {
		if (
			parseInt(cardNumber.slice(0, 4) >= 2221) &&
			parseInt(cardNumber.slice(0, 4) <= 2720)
		) {
			return true;
		}

		if (
			(parseInt(cardNumber.slice(0, 2)) >= 51) &&
			(parseInt(cardNumber.slice(0, 2)) <= 55)
		) {
			return true;
		}

		return false;
	}

	cardIsUnionPay(cardNumber) {
		if (cardNumber.startsWith(62)) {
			return true;
		}

		return false;
	}

	cardIsVisa(cardNumber) {
		if (cardNumber.startsWith(4)) {
			return true;
		}

		return false;
	}

	displayCardIssuer(cardNumber) {
		if (this.cardIsAmericanExpress(cardNumber)) {
			return this.setState({
				cardIssuer: 'amex',
				cardIssuerName: 'American Express',
			});
		}

		if (this.cardIsDinersClub(cardNumber)) {
			return this.setState({
				cardIssuer: 'diners',
				cardIssuerName: 'Diners Club',
			});
		}

		if (this.cardIsDiscover(cardNumber)) {
			return this.setState({
				cardIssuer: 'discover',
				cardIssuerName: 'Discover',
			});
		}

		if (this.cardIsMaestro(cardNumber)) {
			return this.setState({
				cardIssuer: 'maestro',
				cardIssuerName: 'Maestro' }
			);
		}

		if (this.cardIsVisa(cardNumber)) {
			return this.setState({
				cardIssuer: 'visa',
				cardIssuerName: 'Visa'
			});
		}

		if (this.cardIsMasterCard(cardNumber)) {
			return this.setState({
				cardIssuer: 'mastercard',
				cardIssuerName: 'MasterCard'
			});
		}

		return this.setState({
			cardIssuer: '',
			cardIssuerName: ''
		});
	}

	render() {
		return (
			<form method="post" action="#" id={this.state.blockId}>
				<div className="askell-subscription-picker-form">
					<span className="section-heading">Choose Your Plan</span>
					<div className="askell-form-subscription-container">
						<input
							id={this.state.blockId + '-subscription-radio-1'}
							name="subscription"
							type="radio"
						/>
						<label
							className="inline"
							htmlFor={this.state.blockId + '-subscription-radio-1'}
						>
							Subscription 1 - 1.000 kr
						</label>
					</div>
					<div className="askell-form-subscription-container">
						<input
							id={this.state.blockId + '-subscription-radio-2'}
							name="subscription"
							type="radio"
						/>
						<label
							className="inline"
							htmlFor={this.state.blockId + '-subscription-radio-2'}
						>
							Subscription 2 - 2.000 kr
						</label>
					</div>
				</div>
				<div className="askell-user-info-form">
					<span className="section-heading">Account Information</span>
					<div className="field-container">
						<div className="askell-form-first-name askell-form-field">
							<label htmlFor={this.state.blockId + '-first-name'}>
								First Name
							</label>
							<input
								id={this.state.blockId + '-first-name'}
								name="firstName"
								type="text"
								value={ this.state.firstName }
								onChange={ this.onChangeFirstName }
							/>
						</div>
						<div className="askell-form-last-name flex">
							<label htmlFor={this.state.blockId + '-last-name'}>
								Last Name
							</label>
							<input
								id={this.state.blockId + '-last-name'}
								name="lastName"
								type="text"
								value={ this.state.lastName }
								onChange={ this.onChangeLastName }
							/>
						</div>
					</div>
					<div className="askell-form-email askell-form-field">
						<label htmlFor={this.state.blockId + '-email-address'}>
							Email Address
						</label>
						<input
							id={this.state.blockId + '-email-address'}
							name="emailAddress"
							type="email"
							value={ this.state.emailAddress }
							onChange={ this.onChangeEmailAddress }
						/>
					</div>
					<div className="field-container">
						<div className="askell-form-username askell-form-field">
							<label htmlFor={this.state.blockId + '-username'}>
								Username
							</label>
							<input
								id={this.state.blockId + '-username'}
								name="username"
								type="text"
								value={ this.state.username }
								onChange={ this.onChangeUsername }
							/>
						</div>
						<div className="askell-form-password askell-form-field">
							<label htmlFor={this.state.blockId + '-password'}>
								Password
							</label>
							<input
								id={this.state.blockId + '-password'}
								name="password"
								type="password"
								value={ this.state.password }
								onChange={ this.onChangePassword }
							/>
						</div>
					</div>
					<div className="askell-form-terms-checkbox">
						<input
							id={this.state.blockId + '-terms-checkbox'}
							name="termsAccepted"
							type="checkbox"
							onClick={ this.onChangeTermsAccepted }
						/>
						<label htmlFor={this.state.blockId + '-terms-checkbox'} className="inline">
							I accept the <a href="#">terms of service</a>.
						</label>
					</div>
				</div>
				<div className="askell-cc-info-form">
					<span className="section-heading">Payment Information</span>
					<div className="askell-form-field">
						<label
							htmlFor={this.state.blockId + '-card-holder-name'}
						>
							Card Holder Name
						</label>
						<input
							id={this.state.blockId + '-card-holder-name'}
							type="text"
							name="cardHolderName"
							value={ this.state.cardHolderName }
							onChange={ this.onChangeCardHolderName }
						/>
					</div>
					<div className="askell-form-field">
						<label htmlFor={this.state.blockId + '-card-number'}>
							Card Number
						</label>
						<div className="askell-card-number-form-field">
							<input
								id={this.state.blockId + '-card-number'}
								type="text"
								name="cardNumber"
								value={this.state.cardNumberSpaced}
								onChange={this.onChangeCardNumber}
							/>
							<span className={`issuer ${this.state.cardIssuer}`}>
								{this.state.cardIssuerName}
							</span>
						</div>
					</div>
					<div className="field-container">
						<div
							className="askell-form-field"
							aria-labelledby={this.state.blockId + '-expiry-label'}
						>
							<span
								id={this.state.blockId + '-expiry-label'}
								className="label"
							>
								Card Expiry
							</span>
							<select
								name="cardExpiryMonth"
								aria-label="Month"
								defaultValue={ this.state.cardExpiryMonth }
								onChange={ this.onChangeCardExpiryMonth }
							>
								{[...Array(12)].map((_, i) => (
									<option key={'month-' + i + 1}>{i + 1}</option>
								))}
							</select>
							<select
								name="cardExpiryYear"
								aria-label="Year"
								defaultValue={ this.state.cardExpiryYear }
								onChange={ this.onChangeCardExpiryYear }
							>
								{[...Array(50)].map((_, i) => (
									<option key={'year-' + new Date().getFullYear() + i}>
										{this.state.currentYear + i}
									</option>
								))}
							</select>
						</div>
						<div className="askell-form-field">
							<label
								htmlFor={this.state.blockId + '-security-code'}
							>
								Security Code
							</label>
							<input
								id={this.state.blockId + '-security-code'}
								type="text"
								name="cardSecurityCode"
								value={ this.state.cardSecurityCode }
								onChange={ this.onChangeCardSecurityCode }
							/>
						</div>
					</div>
					<div className="buttons">
						<button
							onClick={ this.onClickUserInformationNextStep }
						>
							Confirm payment and create account
						</button>
					</div>
					<p className="hint">
						Payment processing is performed by this site's owner
						card merchant service, via Askell by Overcast Software,
						which runs over a secure transport layer and is a PCI
						certified recurring payments platform. Payment
						information is sent directly to Askell for processing.
					</p>
				</div>
			</form>
		);
	}
}

export default AskellRegistration;
