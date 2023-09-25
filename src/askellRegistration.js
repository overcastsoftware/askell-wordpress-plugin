import { Button } from '@wordpress/components';

class AskellRegistration extends React.Component {
	constructor(props) {
		const currentYear = new Date().getFullYear();
		super(props);
		this.state = {
			blockId: _.uniqueId('askell-registration-block-'),
			currentYear,
			currentStep: 'plans',
			plans: [],
			selectedPlan: {},
			firstName: '',
			lastName: '',
			emailAddress: '',
			emailAddressIsValid: false,
			username: '',
			password: '',
			termsAccepted: false,
			userInfoChecked: false,
			userID: 0,
			cardHolderName: '',
			cardNumber: '',
			cardNumberSpaced: '',
			cardExpiryMonth: '1',
			cardExpiryYear: currentYear.toString(),
			cardIssuer: '',
			cardIssuerName: '',
			cardSecurityCode: '',
			disableConfirmButton: true,
			WpErrorCode: null,
			WpErrorMessage: null,
			disableNextStepButton: false,
			cleanKennitala: null,
			paymentWindow: null,
			paymentToken: null,
			checkPaymentTokenIntervalID: null,
			registrationToken: null,
			paymentErrorMessage: null
		};
		this.createUser = this.createUser.bind(this);

		this.onChangePlan = this.onChangePlan.bind(this);
		this.onClickPlansNextStep = this.onClickPlansNextStep.bind(this);

		this.onChangeFirstName = this.onChangeFirstName.bind(this);
		this.onChangeLastName = this.onChangeLastName.bind(this);
		this.onChangeEmailAddress = this.onChangeEmailAddress.bind(this);
		this.onChangeUsername = this.onChangeUsername.bind(this);
		this.onChangePassword = this.onChangePassword.bind(this);
		this.onChangeTermsAccepted = this.onChangeTermsAccepted.bind(this);
		this.onClickUserInfoNextStep = this.onClickUserInfoNextStep.bind(this);
		this.onClickUserInfoBackButton =
			this.onClickUserInfoBackButton.bind(this);

		this.onChangeCardHolderName = this.onChangeCardHolderName.bind(this);
		this.onChangeCardNumber = this.onChangeCardNumber.bind(this);
		this.onChangeCardExpiryMonth = this.onChangeCardExpiryMonth.bind(this);
		this.onChangeCardExpiryYear = this.onChangeCardExpiryYear.bind(this);
		this.onChangeCardSecurityCode =
			this.onChangeCardSecurityCode.bind(this);

		this.onClickConfirmPayment = this.onClickConfirmPayment.bind(this);

		this.showSuccess = this.showSuccess.bind(this);
		this.assignPaymentMethod = this.assignPaymentMethod.bind(this);
		this.setPaymentError = this.setPaymentError.bind(this);
		this.clearPaymentError = this.clearPaymentError.bind(this);
	}

	componentDidMount() {
		this.getFormFields();
	}

	async getFormFields() {
		const response = await fetch(
			wpApiSettings.root + 'askell/v1/form_fields',
			{
				method: 'GET',
				cache: 'no-cache'
			}
		);

		const result = await response.json();

		this.setState({
			APIKey: result.api_key,
			reference: result.reference,
			stylesEnabled: result.styles_enabled,
			countrySelectorEnabled: result.address_country_enabled,
			plans: result.plans
		});

		return result;
	}

	async createUser() {
		this.setState({ disableNextStepButton: true })
		const response = await fetch(
			wpApiSettings.root + 'askell/v1/customer',
			{
				method: 'POST',
				cache: 'no-cache',
				headers: {
					'Content-Type': "application/json",
					'X-WP-Nonce': wpApiSettings.nonce
				},
				body: JSON.stringify({
					password: this.state.password,
					username: this.state.username,
					emailAddress: this.state.emailAddress,
					firstName: this.state.firstName,
					lastName: this.state.lastName,
					planId: this.state.selectedPlan.id,
					planReference: this.state.selectedPlan.reference,
					kennitala: this.state.cleanKennitala
				})
			}
		);

		const responseData = await response.json();

		if ( response.ok ) {
			this.setState({
				disableNextStepButton: false,
				userID: responseData['ID'],
				registrationToken: responseData['registration_token'],
				currentStep: 'cc-info',
				disableConfirmButton: false,
				// Take the password out of the state context as it ha been sent
				password: ''
			});
		} else {
			this.setState({
				disableNextStepButton: false,
				WpErrorCode: responseData['code'],
				WpErrorMessage: responseData['message']
			});
		}
	}

	onClickConfirmPayment() {
		this.openPaymentModal();
		this.createTemporaryPaymentMethod();
	}

	openPaymentModal() {
		let paymentWindow = window.open(
			'',
			'askell_payment_window',
			'popup,width=600,height=400'
		);

		window.paymentWindow = paymentWindow;
	}

	async createTemporaryPaymentMethod() {
		this.clearPaymentError();
		const response = await fetch(
			'https://askell.is/api/temporarypaymentmethod/',
			{
				method: 'POST',
				cache: 'no-cache',
				headers: {
					'Content-Type': 'application/json',
					'Authorization': 'Api-Key ' + this.state.APIKey
				},
				body: JSON.stringify({
					card_number: this.state.cardNumber,
					expiration_year: this.state.cardExpiryYear.slice(2),
					expiration_month: this.state.cardExpiryMonth,
					cvv_number: this.state.cardSecurityCode,
					plan: this.state.selectedPlan.id
				})
			}
		);

		const responseData = await response.json();

		if ( response.ok ) {
			let url = responseData.card_verification_url;
			let paymentToken = responseData.token;

			window.paymentWindow.location = url;
			this.setState({ paymentToken: paymentToken });

			this.checkPaymentTokenLoop(
				paymentToken,
				this.state.APIKey,
				this.state.registrationToken,
				this.state.selectedPlan.id,
				this
			);
		} else {
			this.setPaymentError(responseData.error);
			window.paymentWindow.close();
		}
	}

	async checkPaymentToken(paymentToken, apiKey, registrationToken, planID, parent) {
		const response = await fetch(
			'https://askell.is/api/temporarypaymentmethod/' + paymentToken,
			{
				method: 'GET',
				cache: 'no-cache',
				headers: {
					'Content-Type': 'application/json',
					'Authorization': 'Api-Key ' + apiKey
				}
			}
		);

		const responseData = await response.json();

		console.log(responseData);

		if ( response.ok ) {
			if (responseData.status !== 'initial') {
				clearInterval(window.askellTokenIntervalID);
				if ( responseData.status === 'tokencreated' ) {
					parent.assignPaymentMethod(
						paymentToken,
						registrationToken,
						planID,
						parent
					)
				}
			}
		} else {
			parent.setPaymentError(responseData.error);
		}
	}

	checkPaymentTokenLoop(token, APIKey, registrationToken, planID, parent) {
		console.log(parent);
		let intervalID = setInterval(
			this.checkPaymentToken,
			2500,
			token,
			APIKey,
			registrationToken,
			planID,
			parent
		);
		window.askellTokenIntervalID = intervalID;
	}

	async assignPaymentMethod(paymentToken, registrationToken, planID, parent) {
		const response = await fetch(
			wpApiSettings.root + 'askell/v1/customer_payment_method',
			{
				method: 'POST',
				cache: 'no-cache',
				headers: {
					'Content-Type': "application/json",
					'X-WP-Nonce': wpApiSettings.nonce
				},
				body: JSON.stringify({
					paymentToken: paymentToken,
					registrationToken: registrationToken,
					planID: planID
				})
			}
		);

		if ( response.ok ) {
			parent.showSuccess();
		} else {
			parent.setPaymentError(responseData.error);
		}
	}

	showSuccess() {
		this.setState({ currentStep: 'success' });
	}

	setPaymentError(errorMessage) {
		this.setState({ paymentErrorMessage: errorMessage });
	}

	clearPaymentError() {
		this.setState({ paymentErrorMessage: null });
	}

	onFormSubmit(event) {
		event.preventDefault();
	}

	onChangePlan(event) {
		const plan = this.state.plans.find(
			({ id }) => id === parseInt(event.target.value)
		);
		this.setState({
			selectedPlan: plan,
		});
	}

	onClickPlansNextStep(event) {
		event.preventDefault();
		this.setState({ currentStep: 'user-info' });
	}

	onChangeFirstName(event) {
		this.setState({ firstName: event.target.value });
	}

	onChangeLastName(event) {
		this.setState({ lastName: event.target.value });
	}

	onChangeEmailAddress(event) {
		this.setState({
			emailAddress: event.target.value,
			emailAddressIsValid: event.target.validity.valid
		});
	}

	onChangeUsername(event) {
		const sanitisedUsername = event.target.value.replace(
			/([^a-z|0-9])/g,
			''
		);
		this.setState({ username: sanitisedUsername });
	}

	onChangePassword(event) {
		this.setState({ password: event.target.value });
	}

	onChangeTermsAccepted(event) {
		this.setState({ termsAccepted: event.target.checked });
	}

	onClickUserInfoNextStep(event) {
		event.preventDefault();
		this.setState({ userInfoChecked: true })

		// Count the number of invalid elements in the user info section
		let invalidElementCount = document.querySelectorAll(
			'#' + this.state.blockId + ' .askell-user-info-form input:invalid'
		).length

		// Show the credit card form when there are zero validation errors in
		// the user info section.
		// CSS will be used for highlighting invalid fields.
		if ((invalidElementCount == 0) && (this.state.termsAccepted == true)) {
			this.createUser();
		}
	}

	onClickUserInfoBackButton(event) {
		event.preventDefault();
		this.setState({ currentStep: 'plans' });
	}

	onChangeCardHolderName(event) {
		this.setState({ cardHolderName: event.target.value });
	}

	onChangeCardNumber(event) {
		// Sanitise the card number, removing all non-numeric characters. This
		// is the value that is then sent to the API.
		const cleanCardNumber = event.target.value.replace(/[^\d.-]+/g, '');

		// Slices the card number into 4 space-separated sections, which is more
		// human readable. This is the value displayed in the form field.
		const spacedCardNumber = [
			cleanCardNumber.slice(0, 4),
			cleanCardNumber.slice(4, 8),
			cleanCardNumber.slice(8, 12),
			cleanCardNumber.slice(12, 19),
		]
			.filter((portion) => portion !== '')
			.join(' ');

		this.setState({
			cardNumber: cleanCardNumber,
			cardNumberSpaced: spacedCardNumber,
		});

		this.displayCardIssuer(cleanCardNumber);
	}

	onChangeCardExpiryMonth(event) {
		this.setState({ cardExpiryMonth: event.target.value });
	}

	onChangeCardExpiryYear(event) {
		this.setState({ cardExpiryYear: event.target.value });
	}

	onChangeCardSecurityCode(event) {
		const cleanCode = event.target.value
			.replace(/[^\d.-]+/g, '')
			.slice(0, 4);
		this.setState({ cardSecurityCode: cleanCode });
	}

	cardIsAmericanExpress(cardNumber) {
		if (cardNumber.startsWith(34) || cardNumber.startsWith(37)) {
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
		if (cardNumber.startsWith('6011')) {
			return true;
		}

		if (cardNumber.startsWith('65')) {
			return true;
		}

		if (
			parseInt(cardNumber.slice(0, 3)) >= 644 &&
			parseInt(cardNumber.slice(0, 3) <= 649)
		) {
			return true;
		}

		if (
			parseInt(cardNumber.slice(0, 6)) >= 622126 &&
			parseInt(cardNumber.slice(0, 6) <= 622925)
		) {
			return true;
		}

		return false;
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
			parseInt(cardNumber.slice(0, 2)) >= 51 &&
			parseInt(cardNumber.slice(0, 2)) <= 55
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
				cardIssuerName: 'Maestro',
			});
		}

		if (this.cardIsVisa(cardNumber)) {
			return this.setState({
				cardIssuer: 'visa',
				cardIssuerName: 'Visa',
			});
		}

		if (this.cardIsMasterCard(cardNumber)) {
			return this.setState({
				cardIssuer: 'mastercard',
				cardIssuerName: 'MasterCard',
			});
		}

		return this.setState({
			cardIssuer: '',
			cardIssuerName: '',
		});
	}

	render() {
		return (
			<form
				id={this.state.blockId}
				method="post"
				action="#"
				data-current-step={ this.state.currentStep }
				onSubmit={ this.onFormSubmit }
			>
				<div
					className="askell-plan-picker-form askell-step"
					onChange={this.onChangePlan}
					aria-labelledby={this.state.blockId + '-plan-section-heading'}
				>
					<span
						id={this.state.blockId + '-plan-section-heading'}
						className="section-heading"
						role="heading"
					>
						Choose Your Plan
					</span>
					<div
						className="askell-form-plans"
					>
						{this.state.plans.map((p, i) => (
							<div
								id={'askell-form-plan-container-' + i}
								className="askell-form-plan-container"
								key={p.id}
								aria-labelledby={this.state.blockId + '-plan-name-' + p.id}
							>
								<input
									id={this.state.blockId + '-plan-radio-' + p.id}
									name="plan"
									type="radio"
									value={p.id}
								/>
								<label
									className=""
									htmlFor={
										this.state.blockId + '-plan-radio-' + p.id
									}
								>
									<span
										id={this.state.blockId + '-plan-name-' + p.id}
										className="plan-name"
										role="heading"
									>
										{p.name}
									</span>
									{p.description !== '' && (
										<p className="description">
											{p.description}
										</p>
									)}
									<em className="price">{p.price_tag}</em>
								</label>
							</div>
						))}
					</div>
					<div className="buttons">
						<Button
							variant="primary"
							size="default"
							disabled={ (this.state.selectedPlan.id === undefined) }
							onClick={this.onClickPlansNextStep}
						>
							Next Step
						</Button>
					</div>
				</div>
				<div
					className="askell-user-info-form askell-step"
					aria-labelledby={this.state.blockId + '-user-info-section-heading'}
					data-checked={ this.state.userInfoChecked }
				>
					<span
						id={this.state.blockId + '-user-info-section-heading'}
						className="section-heading"
						role="heading"
					>
						Account Information
					</span>
					<p className="payment-info">
						Here, we will create a new user for you on this site.
						It is nessecary to enter your information into all the
						fields below in order to get to the next step, where you
						will enter your payment information.
					</p>
					<div className="field-container">
						<div className="askell-form-first-name askell-form-field">
							<label htmlFor={this.state.blockId + '-first-name'}>
								First Name
							</label>
							<input
								id={this.state.blockId + '-first-name'}
								name="firstName"
								type="text"
								value={this.state.firstName}
								required
								minLength="2"
								onChange={this.onChangeFirstName}
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
								value={this.state.lastName}
								required
								minLength="2"
								onChange={this.onChangeLastName}
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
							value={this.state.emailAddress}
							required
							onChange={this.onChangeEmailAddress}
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
								value={this.state.username}
								required
								minLength="1"
								maxLength="60"
								onChange={this.onChangeUsername}
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
								value={this.state.password}
								required
								minLength="8"
								onChange={this.onChangePassword}
							/>
						</div>
					</div>
					<div className="askell-form-terms-checkbox">
						<input
							id={this.state.blockId + '-terms-checkbox'}
							name="termsAccepted"
							type="checkbox"
							onClick={this.onChangeTermsAccepted}
						/>
						<label
							htmlFor={this.state.blockId + '-terms-checkbox'}
							className="inline"
						>
							I accept the <a href="#">terms of service</a>.
						</label>
					</div>
					<div className={ 'error ' + this.state.WpErrorCode } role="alert" aria-live="assertive">
						{ this.state.WpErrorCode !== null &&
							<p>Error: { this.state.WpErrorMessage }</p>
						}
					</div>
					<div className="buttons">
						<Button
							variant="primary"
							size="default"
							onClick={ this.onClickUserInfoBackButton }
						>
							Back
						</Button>
						<span> </span>
						<Button
							variant="primary"
							size="default"
							onClick={ this.onClickUserInfoNextStep }
							disabled={ ( ! this.state.termsAccepted || this.state.disableNextStepButton ) }
						>
							Create Account
						</Button>
					</div>
				</div>
				<div className="askell-cc-info-form askell-step">
					<span className="section-heading">Payment Information</span>
					<p className="payment-info">
						{this.state.selectedPlan.payment_info}
					</p>
					<div
						className="askell-form-card-holder-name askell-form-field"
					>
						<label
							htmlFor={this.state.blockId + '-card-holder-name'}
						>
							Card Holder Name
						</label>
						<input
							id={this.state.blockId + '-card-holder-name'}
							type="text"
							name="cardHolderName"
							autoComplete="off"
							value={this.state.cardHolderName}
							onChange={this.onChangeCardHolderName}
						/>
					</div>
					<div className="askell-form-card-number askell-form-field">
						<label htmlFor={this.state.blockId + '-card-number'}>
							Card Number
						</label>
						<div className="askell-card-number-form-field">
							<input
								id={this.state.blockId + '-card-number'}
								type="text"
								name="cardNumber"
								autoComplete="off"
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
							className="askell-form-card-expiry askell-form-field"
							aria-labelledby={
								this.state.blockId + '-expiry-label'
							}
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
								autoComplete="false"
								defaultValue={this.state.cardExpiryMonth}
								onChange={this.onChangeCardExpiryMonth}
							>
								{[...Array(12)].map((_, i) => (
									<option key={'month-' + i + 1}>
										{i + 1}
									</option>
								))}
							</select>
							<select
								name="cardExpiryYear"
								aria-label="Year"
								autoComplete="off"
								defaultValue={this.state.cardExpiryYear}
								onChange={this.onChangeCardExpiryYear}
							>
								{[...Array(50)].map((_, i) => (
									<option
										key={
											'year-' +
											new Date().getFullYear() +
											i
										}
									>
										{this.state.currentYear + i}
									</option>
								))}
							</select>
						</div>
						<div className="askell-form-card-security-code askell-form-field">
							<label
								htmlFor={this.state.blockId + '-security-code'}
							>
								Security Code
							</label>
							<input
								id={this.state.blockId + '-security-code'}
								type="text"
								name="cardSecurityCode"
								autoComplete="off"
								value={this.state.cardSecurityCode}
								onChange={this.onChangeCardSecurityCode}
							/>
						</div>
					</div>
					<div className={ 'error ' } role="alert" aria-live="assertive">
						{ this.state.paymentErrorMessage !== null &&
							<p>Error: { this.state.paymentErrorMessage }</p>
						}
					</div>
					<div className="buttons">
						<button
							disabled={ this.state.disableConfirmButton }
							onClick={this.onClickConfirmPayment}
						>
							Confirm Payment
						</button>
					</div>
					<p className="hint">
						Payment processing is performed by this site&apos;s
						owner&apos; card merchant service, via Askell by
						Overcast Software, which runs over a secure transport
						layer and is a PCI certified recurring payments
						platform. Payment information is sent directly to Askell
						for processing.
					</p>
				</div>
				<div className="askell-success-form askell-step">
					<span className="section-heading">Success!</span>
					<p>
						You should have received a confirmation email with the
						relevant details about how to edit or cancel your
						subscription.
					</p>
				</div>
			</form>
		);
	}
}

export default AskellRegistration;
