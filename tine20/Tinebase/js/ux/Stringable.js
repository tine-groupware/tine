class Stringable extends String {
    constructor (s, toString) {
        super(s)
        this.toString = toString
    }
}

export default Stringable