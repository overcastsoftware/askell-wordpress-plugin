class AskellRegistration extends React.Component {
	constructor(props) {
		super(props);
		this.state = {
			blockId: _.uniqueId('askel-registration-block-'),
			currentYear: new Date().getFullYear(),
			cardNumber: '',
			cardIssuer: '',
			cardIssuerName: '',
			cardSecurityCode: ''
		};
		this.onChangeCardNumber = this.onChangeCardNumber.bind(this);
		this.onChangeCardSecurityCode = this.onChangeCardSecurityCode.bind(this);
	}

	currentYear() {
		return new Date().getFullYear();
	}

	onChangeCardSecurityCode(event) {
		let cleanCode = event.target.value.replace(/[^\d.-]+/g, '').substring(0,4);
		this.setState( { cardSecurityCode: cleanCode } );
	}

	onChangeCardNumber(event) {
		this.setState({ cardNumber: event.target.value.replace(/[^\d.-]+/g, '') } );

		if (event.target.value === '') {
			this.setState({ cardNumber: '' });
		}

		if (this.cardIsAmericanExpress(event.target.value)) {
			this.setState({
				cardIssuer: 'amex',
				cardIssuerName: 'American Express',
			});
		} else if (this.cardIsDinersClub(event.target.value)) {
			this.setState({
				cardIssuer: 'diners',
				cardIssuerName: 'Diners Club',
			});
		} else if (this.cardIsDiscover(event.target.value)) {
			this.setState({
				cardIssuer: 'discover',
				cardIssuerName: 'Discover',
			});
		} else if (this.cardIsMaestro(event.target.value)) {
			this.setState({ cardIssuer: 'maestro', cardIssuerName: 'Maestro' });
		} else if (this.cardIsVisa(event.target.value)) {
			this.setState({ cardIssuer: 'visa', cardIssuerName: 'Visa' });
		} else if (this.cardIsMasterCard(event.target.value)) {
			this.setState({ cardIssuer: 'mastercard', cardIssuerName: 'MasterCard' });
		} else {
			this.setState({ cardIssuer: '', cardIssuerName: '' });
		}
	}

	cardIsAmericanExpress(cardNumber) {
		if (
			(cardNumber.startsWith(34) || cardNumber.startsWith(37)) &&
			cardNumber.length === 15
		) {
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
		if (
			cardNumber.startsWith('6011') ||
			cardNumber.startsWith('65') ||
			(parseInt(cardNumber.substring(0, 3)) >= 644 &&
				parseInt(cardNumber.substring(0, 3) <= 649)) ||
			(parseInt(cardNumber.substring(0, 6)) >= 622126 &&
				parseInt(cardNumber.substring(0, 6) <= 622925))
		) {
			return true;
		}
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
			parseInt(cardNumber.substring(0, 4) >= 2221) &&
			parseInt(cardNumber.substring(0, 4) <= 2720)
		) {
			return true;
		}

		if (
			(parseInt(cardNumber.substring(0, 2)) >= 51) &&
			(parseInt(cardNumber.substring(0, 2)) <= 55)
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

	render() {
		return (
			<form method="post" action="#" id={this.state.blockId}>
				<div className="askell-user-info-form">
					<span className="section-heading">User information</span>
					<div className="field-container">
						<div className="askell-form-first-name askell-form-field">
							<label htmlFor={this.state.blockId + '-first-name'}>
								First Name
							</label>
							<input
								id={this.state.blockId + '-first-name'}
								name="firstName"
								type="text"
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
							/>
						</div>
					</div>
					<div className="askell-form-terms-checkbox">
						<input
							id={this.state.blockId + '-terms-checkbox'}
							name="termsAccepted"
							type="checkbox"
						/>
						<label htmlFor={this.state.blockId + '-terms-checkbox'} className="inline">
							I accept the <a href="#">terms of service</a>.
						</label>
					</div>
					<div className="buttons">
						<button>Next step</button>
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
								value={this.state.cardNumber}
								onChange={this.onChangeCardNumber}
							/>
							<span className={`issuer ${this.state.cardIssuer}`}>
								{this.state.cardIssuerName}
							</span>
						</div>
					</div>
					<div className="field-container">
						<div className="askell-form-field">
							<label htmlFor={this.state.blockId + '-expiry-date'}>
								Card Expiry
							</label>
							<select
								name="cardExpiryMonth"
								aria-label="Month"
							>
								{[...Array(12)].map((_, i) => (
									<option key={i + 1}>{i + 1}</option>
								))}
							</select>
							<select
								id={this.state.blockId + '-expiry-date'}
								name="cardExpiryYear"
								aria-label="Year"
							>
								{[...Array(50)].map((_, i) => (
									<option key={this.currentYear() + i}>
										{this.state.currentYear + i}
									</option>
								))}
							</select>
						</div>
						<div className="askell-form-field">
							<label htmlFor={this.state.blockId + '-security-code'}>
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
					<p className="hint">
						Payment processing is performed by this site's owner
						card merchant service, via Askell by Overcast software,
						which runs over a secure transport layer and is a PCI
						certified recurring payments platform. Payment
						information is sent directly to Askell for processing.
					</p>
					<div className="buttons">
						<button>Next step</button>
					</div>
				</div>
			</form>
		);
	}
}

export default AskellRegistration;
